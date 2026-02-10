# 📡 Nodi LoRa Italia (WP Plugin)

**Nodi LoRa Italia** è un plugin per WordPress sviluppato per monitorare e visualizzare in tempo reale lo stato della rete **LoRaItalia** sul territorio italiano. Grazie all'integrazione con le API di LoRa Italia, permette di visualizzare i nodi, la loro telemetria e i collegamenti radio direttamente sul tuo sito web.



---

## 🚀 Funzionalità Principali

* **Mappa Interattiva**: Visualizzazione basata su **Leaflet.js** con marker in tempo reale.
* **Focus Regionale**: Pannello admin per selezionare una delle **20 regioni italiane** (o vista nazionale).
* **Filtraggio Intelligente**: Sistema di *bounding box* per mostrare solo i nodi dell'area selezionata.
* **Telemetria Completa**: Tabella con stato batteria, nome nodo e gateway di aggancio.
* **Link Radio**: Linee grafiche che congiungono i nodi ai loro gateway.
* **Calcolo Distanze**: Formula di Haversine per la distanza esatta in km.

---

## 🛠️ Installazione

1. Carica la cartella del plugin in `/wp-content/plugins/`.
2. Attiva il plugin dal menu **Plugin** di WordPress.
3. Vai in **Impostazioni > LoRa Italia**.
4. Inserisci il tuo **Bearer Token API**.
5. Seleziona la **Regione di riferimento**.
6. Salva le modifiche.

---

## 📖 Utilizzo

Usa questo shortcode in qualsiasi pagina o articolo:

`[nodi_loraitalia]`

---

## ⚙️ Dettagli Tecnici

### Ottimizzazione
Il plugin utilizza una logica di **JSON Minification**: PHP elabora i dati grezzi e invia al browser solo i parametri essenziali (`lat`, `lon`, `battery`, `link`), evitando blocchi del browser nella visualizzazione nazionale.

### Logica Geografica
Ogni regione ha coordinate dedicate per evitare sovrapposizioni tra aree confinanti.

---

## 👤 Autore

**Ruben Giancarlo Elmo (IZ7ZKR)** *Radioamatore e sviluppatore, appassionato di tecnologie radio e LoRa.*

---

## 📄 Licenza

Distribuito sotto licenza **GPLv2**.
