<!-- // Layout della pagina di ricerca per singolo URL -->
<div class="wrap">
    <h1>Risultati per Singolo URL</h1>
    <form id="rubik-single-search-form">
        <input type="text" id="search_query" name="search_query" placeholder="Inserisci Post ID, permalink, dominio, anchor text o status">
        <select id="search_type" name="search_type">
                <option value="post_id">Post ID</option>
                <option value="permalink">Permalink</option>
                <option value="domain">Dominio</option>
                <option value="anchor_text">Anchor Text</option>
                <option value="status">Status di Pagina</option>
            </select>
        <button type="button" class="button button-primary" id="search-url">Cerca</button>
    </form>
    <div id="single-search-results"></div>
</div>

<style>
    #sortable-table th {
        color: dodgerblue;
        font-size: 15px;
        font-weight: 500;
        text-decoration: underline;
        cursor: pointer;
    }
</style>
<!-- // JavaScript per gestire la ricerca AJAX e aggiornare l'interfaccia utente -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tablesort/5.2.1/tablesort.min.js"></script>
<script type="text/javascript">
    var rubikTranslations = {
        statusWarning: "<?php echo esc_js(__('Stai cercando un numero che potrebbe corrispondere a uno status di pagina (es. 404, 301). Se è questo il caso, seleziona -Status di Pagina- nel menu a tendina.', 'rubik-plugin')); ?>"
    };
</script>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        const currentDomain = window.location.hostname;
        const validHttpStatuses = [100, 101, 102, 200, 201, 202, 203, 204, 205, 206, 301, 302, 303, 304, 307, 308, 400, 401, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 422, 425, 426, 429, 431, 451, 500, 501, 502, 503, 504, 505, 511];

        // Funzione per determinare automaticamente il tipo di input
        function detectInputType(input) {
            const urlPattern = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w.-]*)*\/?$/;
            const domainPattern = /^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            const isNumeric = !isNaN(input);

            // Se l'input è un numero, consideralo come ID di default a meno che non sia uno status
            if (isNumeric) {
                const inputNumber = parseInt(input, 10);

                // Se è un numero e corrisponde a uno status HTTP valido, mostra l'avviso
                if (validHttpStatuses.includes(inputNumber)) {
                    alert(rubikTranslations.statusWarning);
                    return 'status';
                }

                // Preseleziona "ID" per input numerici
                return 'post_id';
            }

            // Rileva URL e dominio
            if (urlPattern.test(input)) {
                const urlDomain = input.replace(/^(https?:\/\/)?(www\.)?/, '').split('/')[0];
                if (urlDomain === currentDomain) {
                    return 'permalink';
                } else {
                    return 'domain';
                }
            } else if (domainPattern.test(input)) {
                return 'domain';
            }

            // Default: anchor text
            return 'anchor_text';
        }

        // Funzione di ricerca al click del pulsante
        function performSearch() {
            const query = $("#search_query").val();
            const searchType = $("#search_type").val() || detectInputType(query);

            const searchData = {
                action: "rubik_search_links",
                search_query: query,
                search_type: searchType
            };

            $("#single-search-results").html("<p>Ricerca in corso...</p>");

            $.post(ajaxurl, searchData, function(response) {
                if (response.success) {
                    $("#single-search-results").html(response.data.html);

                    // Inizializza Tablesort dopo che la tabella è stata caricata
                    const table = document.getElementById('sortable-table');
                    if (table) {
                        new Tablesort(table);
                    }
                } else {
                    $("#single-search-results").html("<p>Nessun risultato valido.</p>");
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
            if (e.which === 13) {
                e.preventDefault();
                performSearch();
            }
        });

        // Rileva automaticamente il tipo di input
        $("#search_query").on("input", function() {
            const query = $(this).val();
            const detectedType = detectInputType(query);
            $("#search_type").val(detectedType);
        });
    });
</script>