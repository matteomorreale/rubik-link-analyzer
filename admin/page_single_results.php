<?php
// Layout della pagina di ricerca per singolo URL
echo '<div class="wrap">';
echo '<h1>Risultati per Singolo URL</h1>';
echo '<form id="rubik-single-search-form">';
echo '<input type="text" id="search_query" name="search_query" placeholder="Inserisci Post ID, titolo o URL">';
echo '<button type="button" class="button button-primary" id="search-url">Cerca</button>';
echo '</form>';
echo '<div id="single-search-results"></div>';
echo '</div>';

// JavaScript per gestire la ricerca AJAX e aggiornare l'interfaccia utente
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Funzione di ricerca al click del pulsante
    function performSearch() {
        var searchData = {
            action: "rubik_search_links",
            search_query: $("#search_query").val()
        };

        $("#single-search-results").html("<p>Ricerca in corso...</p>");

        $.post(ajaxurl, searchData, function(response) {
            if (response.success) {
                // Se il post esiste, esegue una nuova scansione per aggiornare i dati
                var postId = response.data.post_id;
                var scanData = {
                    action: "rubik_scan_single_post",
                    post_id: postId
                };

                $.post(ajaxurl, scanData, function(scanResponse) {
                    if (scanResponse.success) {
                        // Mostra i risultati aggiornati
                        $("#single-search-results").html(response.data.html);
                    } else {
                        console.error("Errore durante la scansione:", scanResponse);
                        $("#single-search-results").html("<p>Errore durante la scansione.</p>");
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("Errore AJAX durante la scansione:", textStatus, errorThrown);
                    $("#single-search-results").html("<p>Errore durante la scansione. Dettagli dell'errore: " + errorThrown + "</p>");
                });
            } else {
                console.error("Errore durante la ricerca:", response);
                $("#single-search-results").html("<p>Errore durante la ricerca.</p>");
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Errore AJAX:", textStatus, errorThrown);
            $("#single-search-results").html("<p>Errore durante la ricerca. Dettagli dell'errore: " + errorThrown + "</p>");
        });
    }

    // Evento click sul pulsante di ricerca
    $("#search-url").click(function() {
        performSearch();
    });

    // Evento pressione del tasto invio nel campo di ricerca
    $("#search_query").keypress(function(e) {
        if (e.which == 13) { // Codice 13 corrisponde al tasto Enter
            e.preventDefault();
            performSearch();
        }
    });
});
</script>

<?php
// Verifica se una query di ricerca è stata effettuata
if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
    $search_query = sanitize_text_field($_GET['search_query']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'rubik_link_data';

    // Prova a identificare se `search_query` è un ID, un URL o un titolo
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
        } else {
            echo '<p>Nessun risultato trovato per la tua ricerca.</p>';
        }
    } else {
        echo '<p>Nessun risultato trovato per la tua ricerca.</p>';
    }
}
