# Rubik Link Analyzer

**Rubik Link Analyzer** è un plugin per WordPress progettato per analizzare i link presenti negli articoli del sito. Offre strumenti per scansionare, analizzare e visualizzare informazioni dettagliate sui link, come i link interni, esterni, follow, nofollow e sponsorizzati.

## Funzionalità Principali

- **Scansione dei Link**: Permette di scansionare i link presenti negli articoli di WordPress, identificando i link interni, esterni, follow, nofollow e sponsorizzati.
- **Gestione dei Risultati**: Visualizzazione dei risultati della scansione, inclusi i link trovati, il loro stato HTTP, il tipo di link, l'anchor text e altri dettagli rilevanti.
- **Ricerca Avanzata**: Ricerca per singolo URL, titolo o ID del post, con possibilità di aggiornare i dati dei link tramite scansioni ad-hoc.
- **Filtri Personalizzati**: Filtraggio dei link per data, tipo di link (follow, nofollow, sponsored), domini più linkati, anchor text più utilizzate, e altro.
- **Scansione Programmata**: Possibilità di impostare un cronjob per scansionare automaticamente i nuovi articoli non ancora presenti nel database.
- **Sistema di Aggiornamento del Plugin**: Controllo automatico degli aggiornamenti del plugin tramite JSON, con la possibilità di aggiornare direttamente dalla dashboard di WordPress.

## Requisiti

- **WordPress**: Versione 5.0 o superiore.
- **PHP**: Versione 7.4 o superiore.

## Installazione

1. Scaricare il file ZIP del plugin.
2. Accedere alla dashboard di amministrazione di WordPress.
3. Andare in **Plugin > Aggiungi Nuovo**.
4. Caricare il file ZIP e cliccare su **Installa Ora**.
5. Attivare il plugin dopo l'installazione.

## Configurazione

- Dopo l'attivazione, il plugin aggiungerà un nuovo menu chiamato **Link Analyzer** nel pannello di amministrazione di WordPress.
- Potrai accedere alle pagine di **Scansione**, **Risultati** e **Risultati per singolo URL** per gestire i link del tuo sito.

## Utilizzo

### Scansione dei Link
- Vai alla pagina **Scansione** per avviare la scansione degli articoli selezionando:
  - Tutti gli articoli.
  - Solo gli articoli non ancora scansionati.
  - Articoli pubblicati in un determinato intervallo di date.
- Seleziona i custom post type da includere nella scansione.

### Visualizzazione dei Risultati
- Vai alla pagina **Risultati** per visualizzare gli ultimi 10 link trovati e applicare filtri per un'analisi più approfondita.
- Puoi filtrare i link per data, tipo di link (follow, nofollow, sponsored), e altro.

### Ricerca per Singolo URL o ID
- Utilizza la pagina **Risultati per singolo URL** per cercare un link specifico tramite URL, ID o titolo del post.
- Puoi aggiornare i dati di scansione di un post eseguendo una scansione dedicata tramite la ricerca.

## Aggiornamenti del Plugin

Il plugin è dotato di un sistema di aggiornamento automatico che consente di:
- Verificare periodicamente la disponibilità di nuove versioni tramite un file JSON ospitato su un server remoto.
- Scaricare ed installare l'ultima versione disponibile direttamente dalla dashboard di WordPress.

## Cronjob per la Scansione Programmata

Per automatizzare la scansione dei nuovi articoli ogni giorno:
- Viene impostato un cronjob che ogni mattina alle 4:00 scansiona i nuovi articoli non presenti nel database.

## Contribuire

- **Segnalazione Bug**: Per segnalare bug o problemi, apri una issue su GitHub.
- **Pull Request**: Le PR sono benvenute! Se desideri migliorare il plugin, proponi pure le tue modifiche.

## Licenza

Questo progetto è distribuito sotto la licenza **GPLv2 o successiva**. Sentiti libero di utilizzarlo e modificarlo secondo i termini della licenza.

## Autore

**Matteo Morreale** - [Sito Web](https://matteomorreale.it)

## Contatti

Per qualsiasi domanda o richiesta, puoi contattarmi direttamente su [matteomorreale.it](https://matteomorreale.it).