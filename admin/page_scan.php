<!-- Layout della pagina di scansione -->
<style>
    #scan-status {
        border-radius: 5px;
        background-color: white;
        padding: 1em 3em;
        max-width: 550px;
        margin-top: 2em;
        max-height: 400px;
        overflow: scroll;
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
            if (response.success) {
                updateScanStatus("Articolo " + (index + 1) + " di " + total + " scansionato: " + response.data.message);
            } else {
                console.error("Errore durante la scansione del post ID " + postId + ":", response);
                updateScanStatus("Errore durante la scansione dell'articolo " + (index + 1) + ": " + postId);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Errore AJAX durante la scansione del post ID " + postId + ":", textStatus, errorThrown);
            updateScanStatus("Errore durante la scansione dell'articolo " + (index + 1) + " di " + total + ": Dettagli dell'errore: " + errorThrown);
        });
    }

    function resumeScan(postIds) {
        if (!Array.isArray(postIds)) {
            console.error("postIds non è un array:", postIds);
            return;
        }

        var total = postIds.length;
        postIds.forEach(function(postId, index) {
            setTimeout(function() {
                scanPost(postId, index, total);
            }, index * 800); // Timeout per evitare il sovraccarico
        });
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
    $scanStatus.prepend("<p>" + message + "</p>");
    var $logEntries = $scanStatus.find("p");
    if ($logEntries.length > 100) {
        $logEntries.slice(100).remove();
    }
}
</script>