<?php
/**
 * Plugin Name: Rubik Link Analyzer
 * Description: Plugin per l'analisi dei link presenti negli articoli WordPress.
 * Version: 1.0.1
 * Author: Matteo Morreale
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Definisco una costante per la versione corrente del plugin
define('RUBIK_LINK_ANALYZER_VERSION', '1.0.1');

class Rubik_Link_Analyzer {
    private static $instance = null;
    private $table_name;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rubik_link_data';

        // Hook per aggiungere il menu del plugin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Hook all'attivazione del plugin per creare le tabelle
        register_activation_hook(__FILE__, array($this, 'create_database_table'));
        
        // Hook per verificare le tabelle all'inizializzazione
        add_action('plugins_loaded', array($this, 'check_database_table'));

        // Hook all'attivazione del plugin per creare le tabelle e registrare il cronjob
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
        
        // Hook alla disattivazione per rimuovere il cronjob
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

        // Registrazione dell'evento del cronjob
        add_action('rubik_daily_scan_event', array($this, 'daily_scan_unsaved_posts'));
        
        // Registrazione delle azioni AJAX
        add_action('wp_ajax_rubik_fetch_post_ids', array($this, 'ajax_fetch_post_ids'));
        add_action('wp_ajax_rubik_scan_single_post', array($this, 'ajax_scan_single_post'));
        add_action('wp_ajax_rubik_search_links', array($this, 'ajax_search_links'));

        // Hook per l'aggiornamento del plugin
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_plugin_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
    }

    // Funzione di attivazione del plugin
    public function plugin_activation() {
        // Crea la tabella al momento dell'attivazione
        $this->create_database_table();

        // Pianifica l'evento se non è già pianificato
        if (!wp_next_scheduled('rubik_daily_scan_event')) {
            wp_schedule_event(strtotime('04:00:00'), 'daily', 'rubik_daily_scan_event');
        }
    }

    // Funzione di disattivazione del plugin
    public function plugin_deactivation() {
        // Cancella l'evento di cron quando il plugin viene disattivato
        $timestamp = wp_next_scheduled('rubik_daily_scan_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'rubik_daily_scan_event');
        }
    }

    // Funzione per la scansione giornaliera degli articoli non scansionati
    public function daily_scan_unsaved_posts() {
        global $wpdb;

        $post_types = array('post', 'page');

        // Ottieni tutti gli ID post pubblicati
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );

        // Esegui la query per ottenere tutti gli ID post pubblicati
        $query = new WP_Query($args);
        $all_post_ids = $query->posts;

        // Recupera tutti gli ID post già scansionati dalla tabella personalizzata
        $scanned_post_ids = $wpdb->get_col("SELECT DISTINCT post_id FROM {$this->table_name}");

        // Filtra gli ID degli articoli non presenti nella lista degli articoli già scansionati
        $post_ids = array_diff($all_post_ids, $scanned_post_ids);

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);

            if ($post) {
                $content = $post->post_content;

                // Trova tutti i link nel contenuto
                preg_match_all('/<a\s+(?:[^>]*?\s+)?href=["\']([^"\']*)["\'].*?>(.*?)<\/a>/i', $content, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $link = esc_url_raw($match[1]);
                    $anchor_text = sanitize_text_field($match[2]);
                    $link_type = strpos($link, home_url()) !== false ? 'internal' : 'external';

                    // Verifica lo stato del link (ad esempio 200, 404, ecc.)
                    $response = wp_remote_head($link);
                    $link_status = is_wp_error($response) ? 'error' : wp_remote_retrieve_response_code($response);

                    // Inserisci il link nella tabella del database
                    $wpdb->insert(
                        $this->table_name,
                        array(
                            'post_id' => $post_id,
                            'link' => $link,
                            'link_type' => $link_type,
                            'link_status' => $link_status,
                            'anchor_text' => $anchor_text,
                            'date_discovered' => $date_discovered = date('Y-m-d H:i:s')
                        ),
                        array('%d', '%s', '%s', '%s', '%s')
                    );
                }
            }
        }
    }

    public function create_database_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            link VARCHAR(2083) NOT NULL,
            link_type VARCHAR(20) NOT NULL,
            link_status VARCHAR(20) NOT NULL,
            anchor_text VARCHAR(255) NOT NULL,
            date_discovered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        if ($wpdb->last_error) {
            $this->log_error('Errore nella creazione della tabella: ' . $wpdb->last_error);
        }
    }

    public function check_database_table() {
        global $wpdb;

        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            $this->create_database_table();
            if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
                $this->log_error('Impossibile creare la tabella. Il plugin potrebbe non funzionare correttamente.');
            }
        }
    }

    private function log_error($message) {
        error_log($message);
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        });
    }

    public function add_admin_menu() {
        add_menu_page(
            'Rubik Link Analyzer',
            'Link Analyzer',
            'manage_options',
            'rubik_link_analyzer',
            array($this, 'display_scan_page'),
            'dashicons-admin-links',
            20
        );

        add_submenu_page(
            'rubik_link_analyzer',
            'Scansione',
            'Scansione',
            'manage_options',
            'rubik_link_analyzer',
            array($this, 'display_scan_page')
        );

        add_submenu_page(
            'rubik_link_analyzer',
            'Risultati',
            'Risultati',
            'manage_options',
            'rubik_link_results',
            array($this, 'display_results_page')
        );

        add_submenu_page(
            'rubik_link_analyzer',
            'Risultati per singolo URL',
            'Risultati per URL',
            'manage_options',
            'rubik_link_single_results',
            array($this, 'display_single_results_page')
        );
    }

    public function display_scan_page() {
        require_once plugin_dir_path(__FILE__) . 'admin/page_scan.php';
    }

    public function display_results_page() {
        require_once plugin_dir_path(__FILE__) . 'admin/page_results.php';
    }

    public function display_single_results_page() {
        require_once plugin_dir_path(__FILE__) . 'admin/page_single_results.php';
    }

    // Funzione AJAX per recuperare gli ID dei post da scansionare
    public function ajax_fetch_post_ids() {
        global $wpdb;

        $scan_type = isset($_POST['scan_type']) ? sanitize_text_field($_POST['scan_type']) : 'all';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $post_types = isset($_POST['post_types']) ? (array) $_POST['post_types'] : array('post', 'page');

        // Definisci la query per gli articoli
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );

        // Seleziona gli articoli in base all'intervallo di date
        if ($scan_type == 'date_range' && $start_date && $end_date) {
            $args['date_query'] = array(
                array(
                    'after' => $start_date,
                    'before' => $end_date,
                    'inclusive' => true,
                ),
            );
        }

        // Esegui la query per ottenere tutti gli ID post pubblicati
        $query = new WP_Query($args);
        $all_post_ids = $query->posts;

        // Se il tipo di scansione è per gli articoli non ancora scansionati
        if ($scan_type == 'unsaved') {
            // Recupera tutti gli ID post già scansionati dalla tabella personalizzata
            $scanned_post_ids = $wpdb->get_col("SELECT DISTINCT post_id FROM {$this->table_name}");

            // Filtra gli ID degli articoli non presenti nella lista degli articoli già scansionati
            $post_ids = array_diff($all_post_ids, $scanned_post_ids);
        } else {
            // Altrimenti prendi tutti gli articoli
            $post_ids = $all_post_ids;
        }

        // Restituisci gli ID dei post tramite AJAX
        wp_send_json_success(array('post_ids' => $post_ids));
    }

    // Funzione AJAX per la scansione di un singolo articolo
    public function ajax_scan_single_post() {
        global $wpdb;

        // Inizia a catturare l'output
        ob_start();

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if ($post_id) {
            $post = get_post($post_id);

            if ($post) {
                $content = $post->post_content;

                // Trova tutti i link nel contenuto
                preg_match_all('/<a\s+(?:[^>]*?\s+)?href=["\']([^"\']*)["\'].*?>(.*?)<\/a>/i', $content, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $link = esc_url_raw($match[1]);
                    $anchor_text = sanitize_text_field($match[2]);
                    $link_type = strpos($link, home_url()) !== false ? 'internal' : 'external';

                    // Verifica lo stato del link (ad esempio 200, 404, ecc.)
                    $response = wp_remote_head($link);
                    $link_status = is_wp_error($response) ? 'error' : wp_remote_retrieve_response_code($response);

                    // Controlla se il link è già presente nel database per questo post
                    $existing_link = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$this->table_name} WHERE post_id = %d AND link = %s",
                        $post_id,
                        $link
                    ));

                    if ($existing_link) {
                        // Se il link esiste già, aggiorna il record senza modificare la data di scoperta
                        $wpdb->update(
                            $this->table_name,
                            array(
                                'link_type' => $link_type,
                                'link_status' => $link_status,
                                'anchor_text' => $anchor_text
                            ),
                            array('id' => $existing_link->id),
                            array('%s', '%s', '%s'),
                            array('%d')
                        );
                    } else {
                        // Se il link non esiste, inseriscilo
                        $wpdb->insert(
                            $this->table_name,
                            array(
                                'post_id' => $post_id,
                                'link' => $link,
                                'link_type' => $link_type,
                                'link_status' => $link_status,
                                'anchor_text' => $anchor_text,
                                'date_discovered' => current_time('mysql')
                            ),
                            array('%d', '%s', '%s', '%s', '%s')
                        );
                    }
                }

                // Pulisci il buffer di output e disattiva l'output
                ob_clean();
                wp_send_json_success(array('message' => 'Scansione completata con successo.'));
            } else {
                ob_clean();
                wp_send_json_error(array('message' => 'Articolo non trovato.'));
            }
        } else {
            ob_clean();
            wp_send_json_error(array('message' => 'ID dell\'articolo non valido.'));
        }

        // In caso di output non previsto
        ob_end_clean();
        wp_send_json_error(array('message' => 'Errore durante l\'elaborazione della richiesta.'));
    }

    // Aggiungi questa funzione alla classe Rubik_Link_Analyzer
    public function ajax_search_links() {
        global $wpdb;

        $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';
        $table_name = $wpdb->prefix . 'rubik_link_data';

        // Identifica il tipo di ricerca (ID, URL o titolo)
        $post_id = is_numeric($search_query) ? intval($search_query) : null;
        $post_url = filter_var($search_query, FILTER_VALIDATE_URL) ? $search_query : null;
        $post_title = !$post_id && !$post_url ? $search_query : null;

        $post_ids = array();

        if ($post_id) {
            $post_ids[] = $post_id;
        } elseif ($post_url) {
            $post = url_to_postid($post_url);
            if ($post) {
                $post_ids[] = $post;
            }
        } elseif ($post_title) {
            $posts = get_posts(array(
                's' => $post_title,
                'post_type' => 'any',
                'posts_per_page' => -1,
            ));
            foreach ($posts as $post) {
                $post_ids[] = $post->ID;
            }
        }

        if (!empty($post_ids)) {
            $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
            $query = "SELECT * FROM {$table_name} WHERE post_id IN ($placeholders)";
            $results = $wpdb->get_results($wpdb->prepare($query, ...$post_ids));

            if ($results) {
                ob_start(); // Start buffering output
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>ID</th><th>Post ID</th><th>Link</th><th>Anchor Text</th><th>Link Status</th><th>Data Scoperta</th></tr></thead><tbody>';
                foreach ($results as $row) {
                    echo '<tr>';
                    echo '<td>' . esc_html($row->id) . '</td>';
                    echo '<td>' . esc_html($row->post_id) . ' (<a href="' . get_edit_post_link($row->post_id) . '" target="_blank">Modifica</a> - <a href="' . get_permalink($row->post_id) . '" target="_blank">Visualizza</a>)</td>';
                    echo '<td><a href="' . esc_url($row->link) . '" target="_blank">' . esc_html($row->link) . '</a></td>';
                    echo '<td>' . esc_html($row->anchor_text) . '</td>';
                    echo '<td>' . esc_html($row->link_status) . '</td>';
                    echo '<td>' . esc_html($row->date_discovered) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                $html = ob_get_clean(); // Get the buffered output

                wp_send_json_success(array('html' => $html, 'post_id' => $post_ids[0]));
            } else {
                wp_send_json_error(array('message' => 'Nessun risultato trovato per la tua ricerca.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Nessun risultato trovato per la tua ricerca.'));
        }
    }
    // Funzione per verificare la presenza di aggiornamenti
    public function check_for_plugin_update($transient) {
        // Se l'oggetto non contiene aggiornamenti, ritorna subito
        if (empty($transient->checked)) {
            return $transient;
        }

        // URL del file JSON con i dettagli dell'aggiornamento
        $remote_url = 'https://server4.madeit.srl/mdit_wp_updates/rubik-link-analyzer/update-info.json';
        
        // Recupera i dati della versione remota
        $response = wp_remote_get($remote_url);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response));
            
            // Controlla se esiste una versione più recente rispetto a quella attuale
            if ($data && version_compare($data->version, RUBIK_LINK_ANALYZER_VERSION, '>')) {
                $plugin_data = get_plugin_data(__FILE__);
                $plugin_slug = plugin_basename(__FILE__);
                
                $transient->response[$plugin_slug] = (object) [
                    'slug'        => $plugin_slug,
                    'new_version' => $data->version,
                    'url'         => $data->download_url,
                    'package'     => $data->download_url,
                    'tested'      => '6.7',  // Versione testata di WordPress
                    'requires'    => '5.0',  // Versione minima richiesta
                    'name'        => $plugin_data['Name'],
                    'plugin'      => $plugin_slug,
                    'icons'       => [],
                    'banners'     => [],
                    'banners_rtl' => [],
                ];
            }
        }

        return $transient;
    }

    // Funzione per mostrare le informazioni dell'aggiornamento
    public function plugin_info($res, $action, $args) {
        // Identifica il plugin
        if ($action !== 'plugin_information' || $args->slug !== plugin_basename(__FILE__)) {
            return false;
        }

        // URL del file JSON con i dettagli dell'aggiornamento
        $remote_url = 'https://server4.madeit.srl/mdit_wp_updates/rubik-link-analyzer/update-info.json';
        $response = wp_remote_get($remote_url);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response));
            
            if ($data) {
                $res = (object) [
                    'name'          => 'Rubik Link Analyzer',
                    'slug'          => plugin_basename(__FILE__),
                    'version'       => $data->version,
                    'author'        => 'Matteo Morreale',
                    'author_profile'=> 'https://madeit.srl',
                    'homepage'      => 'https://madeit.srl',
                    'download_link' => $data->download_url,
                    'trunk'         => $data->download_url,
                    'requires'      => '5.0',
                    'tested'        => '6.7',
                    'last_updated'  => $data->last_updated,
                    'sections'      => [
                        'description'  => 'Aggiornamenti per Rubik Link Analyzer.',
                        'changelog'    => $data->changelog,
                    ],
                    'banners' => [],
                    'banners_rtl' => [],
                ];
            }
        }

        return $res;
    }
}

Rubik_Link_Analyzer::get_instance();