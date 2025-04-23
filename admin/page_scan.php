<!-- Layout della pagina di scansione -->
<style>
    #scan-status {
        border-radius: 5px;
        background-color: white;
        padding: 1em 2em;
        width: 100%;
        max-width: 800px;
        margin-top: 2em;
        height: 400px;
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ccc;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    #scan-status p {
        margin: 5px 0;
        padding: 5px;
        border-bottom: 1px solid #f0f0f0;
    }
    #scan-status p:first-child {
        background-color: #f7f7f7;
        font-weight: bold;
    }
    .scan-count {
        position: sticky;
        top: 0;
        background: #fff;
        padding: 10px 0;
        border-bottom: 2px solid #0073aa;
        margin-bottom: 10px;
        font-weight: bold;
        z-index: 100;
    }
</style>
<div class="wrap">
<h1><?php _e("Scansione Link");?></h1>

<!-- Opzioni di scansione: tutto, non scansionati, intervallo di date -->
<form id="rubik-scan-form">
    <p><?php _e("Seleziona il tipo di scansione");?>:</p>
    <select id="scan_type" name="scan_type">
        <option value="all"><?php _e("Tutti gli articoli");?></option>
        <option value="unsaved"><?php _e("Articoli non presenti a database");?></option>
        <option value="date_range"><?php _e("Scansione per intervallo di date");?></option>
    </select>
    <div id="date-range-fields" style="display:none;">
        <label for="start_date"><?php _e("Data inizio");?>:</label>
        <input type="date" id="start_date" name="start_date">
        <label for="end_date"><?php _e("Data fine");?>:</label>
        <input type="date" id="end_date" name="end_date">
    </div>
    <p><?php _e("Seleziona i custom post type da scansionare");?>:</p>

    <?php
    // Lista dei custom post type attivi con checkbox
    $post_types = get_post_types(array('public' => true), 'objects');
    foreach ($post_types as $post_type) {
        if (!in_array($post_type->name, array('attachment'))) {
            echo '<input type="checkbox" name="post_types[]" value="' . esc_attr($post_type->name) . '" id="' . esc_attr($post_type->name) . '">';
            echo '<label for="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</label><br>';
        }
    }
    ?>
    <br/>
    <button type="button" class="button button-primary" id="start-scan"><?php _e("Avvia Scansione");?></button>
    <button type="button" class="button button-secondary" id="delete-data"><?php _e("CANCELLA DATI");?></button>
</form>
<div id="scan-status"></div>
</div>

<!-- JavaScript per gestire la scansione AJAX e aggiornare l'interfaccia utente -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    jQuery("#scan_type").change(function() {
        if (jQuery(this).val() == "date_range") {
            jQuery("#date-range-fields").show();
        } else {
            jQuery("#date-range-fields").hide();
        }
    });

    var scanCount = 0;
    var totalScanCount = 0;

    function fetchPostList(scanData, callback) {
        scanData._ = new Date().getTime();

        jQuery.post(ajaxurl, scanData, function(response) {
            if (response.success) {
                callback(response.data.post_ids);
            } else {
                console.error("Errore durante la scansione iniziale:", response);
                jQuery("#scan-status").html("<p>Errore durante la scansione iniziale.</p>");
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Errore AJAX durante la scansione iniziale:", textStatus, errorThrown);
            jQuery("#scan-status").html("<p>Errore durante la scansione iniziale. Dettagli dell'errore: " + errorThrown + "</p>");
        });
    }

    function scanPost(postId, index, total) {
        jQuery.post(ajaxurl, {
            action: "rubik_scan_single_post",
            post_id: postId
        }, function(response) {
            scanCount++;
            if (response.success) {
                updateScanStatus("Articolo " + (index + 1) + " di " + total + " scansionato: " + response.data.message);
            } else {
                console.error("Errore durante la scansione del post ID " + postId + ":", response);
                updateScanStatus("Errore durante la scansione dell'articolo " + (index + 1) + ": " + postId);
            }
            updateScanCounter(scanCount, total);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            scanCount++;
            console.error("Errore AJAX durante la scansione del post ID " + postId + ":", textStatus, errorThrown);
            updateScanStatus("Errore durante la scansione dell'articolo " + (index + 1) + " di " + total + ": Dettagli dell'errore: " + errorThrown);
            updateScanCounter(scanCount, total);
        });
    }

    function resumeScan(postIds) {
        if (!Array.isArray(postIds)) {
            console.error("postIds non è un array:", postIds);
            return;
        }

        scanCount = 0;
        totalScanCount = postIds.length;
        
        // Aggiungi il contatore in cima
        var $scanStatus = jQuery("#scan-status");
        $scanStatus.html('<div class="scan-count">Scansionati: 0 / ' + totalScanCount + ' articoli</div>');
        
        var total = postIds.length;
        postIds.forEach(function(postId, index) {
            setTimeout(function() {
                scanPost(postId, index, total);
            }, index * 1200); // Timeout per evitare il sovraccarico
        });
    }

    function updateScanCounter(current, total) {
        jQuery(".scan-count").html('Scansionati: ' + current + ' / ' + total + ' articoli');
    }

    jQuery("#start-scan").click(function() {
        var scanData = {
            action: "rubik_fetch_post_ids",
            scan_type: jQuery("#scan_type").val(),
            start_date: jQuery("#start_date").val(),
            end_date: jQuery("#end_date").val(),
            post_types: jQuery("input[name=\"post_types[]\"]:checked").map(function() {
                return jQuery(this).val();
            }).get()
        };

        jQuery("#scan-status").html("<p>Preparazione della scansione...</p>");
        
        fetchPostList(scanData, function(postIds) {
            let primoPost = postIds[0];
            let ultimoPost = postIds[postIds.length - 1];
            let numeroPost = postIds.length;
            jQuery("#scan-status").html("<p>Inizio scansione di "+numeroPost+" articoli... da "+primoPost+" fino a "+ultimoPost+"</p>");
            resumeScan(postIds);
        });
    });

    jQuery("#delete-data").click(function() {
        if (confirm("ATTENZIONE: Stai per cancellare tutti i dati! Questa operazione cancellerà anche le date di prima scoperta dei link. Sei sicuro di voler procedere?")) {
            jQuery.post(ajaxurl, { action: "rubik_delete_all_data" }, function(response) {
                if (response.success) {
                    updateScanStatus("Dati cancellati con successo.");
                } else {
                    console.error("Errore durante la cancellazione dei dati:", response);
                    updateScanStatus("Errore durante la cancellazione dei dati.");
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error("Errore AJAX durante la cancellazione dei dati:", textStatus, errorThrown);
                updateScanStatus("Errore durante la cancellazione dei dati. Dettagli dell'errore: " + errorThrown);
            });
        }
    });
});
function updateScanStatus(message) {
    var $scanStatus = jQuery("#scan-status");
    $scanStatus.append("<p>" + message + "</p>");
    
    // Limita a 500 elementi
    var $logEntries = $scanStatus.find("p");
    if ($logEntries.length > 500) {
        $logEntries.slice(0, $logEntries.length - 500).remove();
    }
    
    // Scorri al fondo per vedere l'ultimo elemento
    $scanStatus.scrollTop($scanStatus[0].scrollHeight);
}
</script>