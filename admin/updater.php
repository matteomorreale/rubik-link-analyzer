<?php
function verifica_aggiornamento_rubik_link_analyzer() {
    $url_endpoint = 'https://server4.madeit.srl/mdit_wp_updates/rubik-link-analyzer/rubik-link-analyzer.php';
    $response = wp_remote_get($url_endpoint);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        return; // Gestisci l'errore come preferisci, ad esempio loggando l'errore o inviando una notifica.
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($data && isset($data['version'], $data['download_url'])) {
        // Salva i dati dell'aggiornamento per un uso successivo
        set_transient('rubik_link_analyzer', $data, HOUR_IN_SECONDS); // Aggiorna ogni ora
    }
}
add_action('admin_init', 'verifica_aggiornamento_rubik_link_analyzer');

function inserisci_aggiornamento_rubik_link_analyzer($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $update_data = get_transient('rubik_link_analyzer');
    if ($update_data) {
        $plugin_file = plugin_basename(RUBIK_LINK_ANALYZER_PLUGIN_FILE);

        if (@version_compare($transient->checked[$plugin_file], @$update_data['version'], '<')) {
            $obj_aggiornamento = new stdClass();
            $obj_aggiornamento->id = 0;
            $obj_aggiornamento->slug = 'rubik-link-analyzer';
            $obj_aggiornamento->plugin = $plugin_file;
            $obj_aggiornamento->new_version = $update_data['version'];
            $obj_aggiornamento->url = $update_data['changelog_url'] ?? ''; // Opzionale: URL del changelog, se disponibile
            $obj_aggiornamento->package = $update_data['download_url'];

            $transient->response[$plugin_file] = $obj_aggiornamento;
        }
    }

    return $transient;
}
add_filter('site_transient_update_plugins', 'inserisci_aggiornamento_rubik_link_analyzer');