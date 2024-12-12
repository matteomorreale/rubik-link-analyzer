<?php
global $wpdb;

// Definizione della tabella
$table_name = $wpdb->prefix . 'rubik_link_data';

// Layout della pagina di risultati
echo '<div class="wrap">';
echo '<h1>Risultati della Scansione</h1>';

// Visualizzazione degli ultimi 10 link inseriti
// Modifica la parte di codice per la visualizzazione dei risultati, aggiungendo la colonna "Rel Attributes"
echo '<h3>Ultimi 10 link inseriti:</h3>';
$recent_results = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY date_discovered DESC LIMIT 10");
if ($recent_results) {
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Post ID</th><th>Link</th><th>Anchor Text</th><th>Link Status</th><th>Tipo di Link</th><th>Rel Attributes</th><th>Data Scoperta</th></tr></thead><tbody>';
    foreach ($recent_results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->post_id) . ' (<a href="' . get_edit_post_link($row->post_id) . '" target="_blank">Modifica</a> - <a href="' . get_permalink($row->post_id) . '" target="_blank">Visualizza</a>)</td>';
        echo '<td><a href="' . esc_url($row->link) . '" target="_blank">' . esc_html($row->link) . '</a></td>';
        echo '<td>' . esc_html($row->anchor_text) . '</td>';
        echo '<td>' . esc_html($row->link_status) . '</td>';
        echo '<td>' . esc_html($row->link_type) . '</td>';
        echo '<td>' . esc_html($row->rel_attributes) . '</td>';
        echo '<td>' . esc_html($row->date_discovered) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>Nessun link trovato.</p>';
}

echo '<hr>';

// Form di filtraggio dei link
echo '<form method="GET" action="">';
echo '<input type="hidden" name="page" value="rubik_link_results">';

echo '<h3>Filtra i link:</h3>';
// Selettore del periodo
echo '<label for="filter_date_preset">Periodo di scoperta link:</label>';
echo '<select id="filter_date_preset" name="filter_date_preset">';
echo '<option value="last_week"' . (($_GET['filter_date_preset'] ?? '') == 'last_week' ? ' selected' : '') . '>Ultima settimana</option>';
echo '<option value="last_30_days"' . (($_GET['filter_date_preset'] ?? 'last_30_days') == 'last_30_days' ? ' selected' : '') . '>Ultimi 30 giorni</option>';
echo '<option value="current_month"' . (($_GET['filter_date_preset'] ?? '') == 'current_month' ? ' selected' : '') . '>Mese corrente</option>';
echo '<option value="previous_month"' . (($_GET['filter_date_preset'] ?? '') == 'previous_month' ? ' selected' : '') . '>Mese precedente</option>';
echo '<option value="last_6_months"' . (($_GET['filter_date_preset'] ?? '') == 'last_6_months' ? ' selected' : '') . '>Ultimi 6 mesi</option>';
echo '<option value="current_year"' . (($_GET['filter_date_preset'] ?? '') == 'current_year' ? ' selected' : '') . '>Anno corrente</option>';
echo '<option value="previous_year"' . (($_GET['filter_date_preset'] ?? '') == 'previous_year' ? ' selected' : '') . '>Anno precedente</option>';
echo '<option value="custom"' . (($_GET['filter_date_preset'] ?? '') == 'custom' ? ' selected' : '') . '>Date a scelta</option>';
echo '</select><br><br>';

// Range di Date per i link inseriti
echo '<div id="custom_date_fields" style="display:' . (($_GET['filter_date_preset'] ?? '') == 'custom' ? 'block' : 'none') . ';">';
echo '<label for="filter_date_start">Data inizio:</label>'; 
echo '<input type="date" id="filter_date_start" name="filter_date_start" value="' . esc_attr($_GET['filter_date_start'] ?? '') . '" placeholder="YYYY-MM-DD">';
echo '<label for="filter_date_end">Data fine:</label>'; 
echo '<input type="date" id="filter_date_end" name="filter_date_end" value="' . esc_attr($_GET['filter_date_end'] ?? '') . '" placeholder="YYYY-MM-DD"><br><br>';
echo '</div>';

// Tipi di link
echo '<label for="filter_link_type">Tipo di link:</label>'; 
echo '<select id="filter_link_type" name="filter_link_type">';
echo '<option value="all"' . (($_GET['filter_link_type'] ?? 'all') == 'all' ? ' selected' : '') . '>Tutti</option>';
echo '<option value="follow"' . (($_GET['filter_link_type'] ?? '') == 'follow' ? ' selected' : '') . '>Follow</option>';
echo '<option value="nofollow"' . (($_GET['filter_link_type'] ?? '') == 'nofollow' ? ' selected' : '') . '>Nofollow</option>';
echo '<option value="sponsored"' . (($_GET['filter_link_type'] ?? '') == 'sponsored' ? ' selected' : '') . '>Sponsored</option>';
echo '<option value="internal"' . (($_GET['filter_link_type'] ?? '') == 'internal' ? ' selected' : '') . '>Interni</option>';
echo '<option value="external"' . (($_GET['filter_link_type'] ?? '') == 'external' ? ' selected' : '') . '>Esterni</option>';
echo '</select><br><br>';

echo '<button type="submit" class="button button-primary">Applica Filtri</button>';
echo '</form><br>';

// JavaScript per mostrare o nascondere il campo delle date personalizzate
echo '<script type="text/javascript">
    document.getElementById("filter_date_preset").addEventListener("change", function() {
        var customFields = document.getElementById("custom_date_fields");
        if (this.value === "custom") {
            customFields.style.display = "block";
        } else {
            customFields.style.display = "none";
        }
    });
</script>';

// Filtri per le query (per risultati filtrati)
$where = " WHERE 1=1 ";

// Applicazione del filtro del periodo
if (!empty($_GET['filter_date_preset'])) {
    switch ($_GET['filter_date_preset']) {
        case 'last_week':
            $where .= $wpdb->prepare(" AND date_discovered >= %s", date('Y-m-d', strtotime('-7 days')));
            break;
        case 'last_30_days':
            $where .= $wpdb->prepare(" AND date_discovered >= %s", date('Y-m-d', strtotime('-30 days')));
            break;
        case 'current_month':
            $where .= $wpdb->prepare(" AND date_discovered >= %s AND date_discovered <= %s", date('Y-m-01'), date('Y-m-t'));
            break;
        case 'previous_month':
            $where .= $wpdb->prepare(" AND date_discovered >= %s AND date_discovered <= %s", date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month')));
            break;
        case 'last_6_months':
            $where .= $wpdb->prepare(" AND date_discovered >= %s", date('Y-m-d', strtotime('-6 months')));
            break;
        case 'current_year':
            $where .= $wpdb->prepare(" AND date_discovered >= %s AND date_discovered <= %s", date('Y-01-01'), date('Y-12-31'));
            break;
        case 'previous_year':
            $where .= $wpdb->prepare(" AND date_discovered >= %s AND date_discovered <= %s", date('Y-01-01', strtotime('-1 year')), date('Y-12-31', strtotime('-1 year')));
            break;
        case 'custom':
            if (!empty($_GET['filter_date_start']) && !empty($_GET['filter_date_end'])) {
                $where .= $wpdb->prepare(" AND date_discovered BETWEEN %s AND %s", sanitize_text_field($_GET['filter_date_start']), sanitize_text_field($_GET['filter_date_end']));
            }
            break;
    }
}

if (!empty($_GET['filter_link_type']) && $_GET['filter_link_type'] !== 'all') {
    if ($_GET['filter_link_type'] === 'follow') {
        $where .= " AND rel_attributes NOT IN ('nofollow', 'sponsored')";
    } elseif ($_GET['filter_link_type'] === 'nofollow') {
        $where .= " AND rel_attributes = 'nofollow'";
    } elseif ($_GET['filter_link_type'] === 'sponsored') {
        $where .= " AND rel_attributes = 'sponsored'";
    } elseif ($_GET['filter_link_type'] === 'internal') {
        $where .= " AND link_type = 'internal'";
    } elseif ($_GET['filter_link_type'] === 'external') {
        $where .= " AND link_type = 'external'";
    }
}

// Visualizzazione dei risultati filtrati
echo '<h3>Risultati Filtrati (max 1000):</h3>';
$filtered_results = $wpdb->get_results("SELECT * FROM {$table_name} $where ORDER BY date_discovered DESC LIMIT 1000");
if ($filtered_results) {
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Post ID</th><th>Link</th><th>Anchor Text</th><th>Link Status</th><th>Tipo di Link</th><th>Rel Attributes</th><th>Data Scoperta</th></tr></thead><tbody>';
    foreach ($filtered_results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->post_id) . ' (<a href="' . get_edit_post_link($row->post_id) . '" target="_blank">Modifica</a> - <a href="' . get_permalink($row->post_id) . '" target="_blank">Visualizza</a>)</td>';
        echo '<td><a href="' . esc_url($row->link) . '" target="_blank">' . esc_html($row->link) . '</a></td>';
        echo '<td>' . esc_html($row->anchor_text) . '</td>';
        echo '<td>' . esc_html($row->link_status) . '</td>';
        echo '<td>' . esc_html($row->link_type) . '</td>';
        echo '<td>' . esc_html($row->rel_attributes) . '</td>';
        echo '<td>' . esc_html($row->date_discovered) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>Nessun link filtrato trovato.</p>';
}

// Visualizzazione delle anchor più usate
echo '<h3>Anchor più usate:</h3>';
$anchor_results = $wpdb->get_results("SELECT anchor_text, COUNT(*) as count FROM {$table_name} $where GROUP BY anchor_text ORDER BY count DESC LIMIT 50");
if ($anchor_results) {
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Anchor Text</th><th>Occorrenze</th></tr></thead><tbody>';
    foreach ($anchor_results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->anchor_text) . '</td>';
        echo '<td>' . esc_html($row->count) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>Nessuna anchor trovata.</p>';
}

// Visualizzazione dei domini più linkati
echo '<h3>Domini più linkati:</h3>';
$domain_results = $wpdb->get_results("SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(link, '/', 3), '/', -1) as domain, COUNT(*) as count FROM {$table_name} $where AND link_type = 'external' GROUP BY domain ORDER BY count DESC");
if ($domain_results) {
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Dominio</th><th>Occorrenze</th></tr></thead><tbody>';
    foreach ($domain_results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->domain) . '</td>';
        echo '<td>' . esc_html($row->count) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>Nessun dominio trovato.</p>';
}

// Aggiunta della sezione per i link in uscita senza testo
echo '<hr>';
echo '<h3>Link in uscita senza testo (anchor vuoto):</h3>';

$empty_anchor_links = $wpdb->get_results("
    SELECT id, post_id, link, link_status, rel_attributes, date_discovered 
    FROM {$table_name} 
    WHERE anchor_text = '' 
    AND link_type = 'external'
    ORDER BY date_discovered DESC
");

if ($empty_anchor_links) {
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Post ID</th><th>Link</th><th>Link Status</th><th>Rel Attributes</th><th>Data di Scoperta</th><th>Azioni</th></tr></thead><tbody>';
    foreach ($empty_anchor_links as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->post_id) . ' (<a href="' . get_edit_post_link($row->post_id) . '" target="_blank">Modifica</a> - <a href="' . get_permalink($row->post_id) . '" target="_blank">Visualizza</a>)</td>';
        echo '<td><a href="' . esc_url($row->link) . '" target="_blank">' . esc_html($row->link) . '</a></td>';
        echo '<td>' . esc_html($row->link_status) . '</td>';
        echo '<td>' . esc_html($row->rel_attributes) . '</td>';
        echo '<td>' . esc_html($row->date_discovered) . '</td>';
        echo '<td><a href="' . esc_url(add_query_arg(['delete_link' => $row->id], admin_url('admin.php?page=rubik_link_results'))) . '" class="button button-secondary">Rimuovi</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>Nessun link in uscita con anchor vuoto trovato.</p>';
}

// Controllo azione di rimozione
if (!empty($_GET['delete_link'])) {
    $link_id = intval($_GET['delete_link']);
    $wpdb->delete($table_name, ['id' => $link_id]);
    echo '<p style="color: green;">Link ID ' . esc_html($link_id) . ' rimosso con successo.</p>';
}


echo '</div>';