<?php
/**
 * Plugin Name: Nodi LoRa Italia
 * Description: Mappa e telemetria dei nodi LoRa Italia con focus regionale. Shortcode  [nodi_loraitalia]
 * Version: 14.1
 * Author: Ruben Giancarlo Elmo IZ7ZKR
 */

if (!defined('ABSPATH')) exit;

class NodiLoraItaliaCompleto {

    private $regions = [
        'italia'        => [35.0, 47.5, 6.5, 18.5, 42.0, 12.5, 6],
        'abruzzo'       => [41.8, 42.9, 13.1, 15.1, 42.3, 14.1, 9],
        'basilicata'    => [39.9, 41.2, 15.3, 16.9, 40.5, 16.1, 9],
        'calabria'      => [37.8, 40.1, 15.5, 17.2, 39.0, 16.5, 8],
        'campania'      => [39.9, 41.6, 13.7, 15.9, 40.8, 14.8, 8],
        'emilia-romagna'=> [43.7, 45.1, 9.2, 12.8, 44.5, 11.0, 8],
        'friuli'        => [45.5, 46.7, 12.2, 14.0, 46.1, 13.1, 9],
        'lazio'         => [41.2, 42.8, 11.4, 14.0, 41.9, 12.7, 8],
        'liguria'       => [43.7, 44.6, 7.5, 10.1, 44.2, 8.8, 9],
        'lombardia'     => [44.7, 46.7, 8.4, 11.5, 45.6, 9.9, 8],
        'marche'        => [42.7, 44.0, 12.2, 14.0, 43.4, 13.1, 8],
        'molise'        => [41.3, 42.1, 14.0, 15.2, 41.7, 14.6, 9],
        'piemonte'      => [44.0, 46.5, 6.6, 9.3, 45.2, 8.0, 8],
        'puglia'        => [39.7, 42.1, 14.8, 18.6, 41.1, 16.7, 8],
        'sardegna'      => [38.8, 41.4, 8.1, 10.0, 40.1, 9.1, 7],
        'sicilia'       => [36.5, 38.4, 12.2, 15.7, 37.5, 14.1, 7],
        'toscana'       => [42.2, 44.5, 9.6, 12.4, 43.4, 11.1, 8],
        'trentino'      => [45.6, 47.1, 10.3, 12.5, 46.4, 11.4, 8],
        'umbria'        => [42.3, 43.6, 11.9, 13.2, 42.9, 12.6, 9],
        'valle-daosta'  => [45.5, 46.0, 6.7, 7.9, 45.7, 7.3, 9],
        'veneto'        => [44.7, 46.7, 10.5, 13.1, 45.7, 11.8, 8]
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('nodi_loraitalia', [$this, 'render_shortcode']);
    }

    public function add_menu() {
        add_options_page('LoRa Italia', 'LoRa Italia', 'manage_options', 'lora-config', [$this, 'admin_page']);
    }

    public function register_settings() {
        register_setting('lora_stable_group', 'lora_api_token');
        register_setting('lora_stable_group', 'lora_region_focus');
    }

    public function admin_page() { ?>
        <div class="wrap">
            <h1>Configurazione Nodi LoRa Italia</h1>
            <form method="post" action="options.php">
                <?php settings_fields('lora_stable_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Token Bearer</th>
                        <td><input type="password" name="lora_api_token" value="<?= esc_attr(get_option('lora_api_token')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Area Focus</th>
                        <td>
                            <select name="lora_region_focus">
                                <?php foreach ($this->regions as $key => $val): ?>
                                    <option value="<?= $key ?>" <?php selected(get_option('lora_region_focus'), $key); ?>><?= ucfirst($key) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php }

    private function get_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return round($earth_radius * $c, 2);
    }

    private function fetch_nodes() {
        $token = get_option('lora_api_token');
        $region_key = get_option('lora_region_focus', 'italia');
        $bounds = $this->regions[$region_key];

        $response = wp_remote_get('https://api.loraitalia.it/public/map/get/nodes', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) return [];
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) return [];

        // Mappa per lookup veloce gateway
        $nodes_map = [];
        foreach($data as $node) { if(isset($node['id'])) $nodes_map[$node['id']] = $node; }

        $filtered = [];
        foreach ($data as $n) {
            $pos = $n['last_position'] ?? null;
            if ($pos) {
                $lat = (float)$pos['latitude'];
                $lon = (float)$pos['longitude'];
                if ($lat >= $bounds[0] && $lat <= $bounds[1] && $lon >= $bounds[2] && $lon <= $bounds[3]) {
                    
                    $dist = '--';
                    $gw_la = null; $gw_lo = null;
                    $gw_name = $n['gateway_node']['long_name'] ?? 'Diretto';
                    $gw_id = $n['gateway_node']['id'] ?? $n['gateway_node'] ?? null;

                    if ($gw_id && isset($nodes_map[$gw_id]['last_position'])) {
                        $gp = $nodes_map[$gw_id]['last_position'];
                        $dist = $this->get_distance($lat, $lon, (float)$gp['latitude'], (float)$gp['longitude']) . " km";
                        $gw_la = (float)$gp['latitude'];
                        $gw_lo = (float)$gp['longitude'];
                    }

                    $filtered[] = [
                        'n' => $n['long_name'] ?? 'N/D',
                        'la' => $lat,
                        'lo' => $lon,
                        'b' => $n['last_device_metric']['battery_level'] ?? '--',
                        'g' => $gw_name,
                        'd' => $dist,
                        'gla' => $gw_la,
                        'glo' => $gw_lo
                    ];
                }
            }
        }
        return $filtered;
    }

    public function render_shortcode() {
        $nodes = $this->fetch_nodes();
        $reg = $this->regions[get_option('lora_region_focus', 'italia')];
        
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);

        ob_start(); ?>
        <div id="lora-app-full">
            <div id="map-lora" style="height: 500px; width: 100%; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ccc;"></div>
            
            <table style="width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 13px;">
                <thead>
                    <tr style="background: #2c3e50; color: #fff; text-align: left;">
                        <th style="padding: 10px;">Nodo</th>
                        <th>Gateway</th>
                        <th>Distanza</th>
                        <th>Batt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nodes as $node): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px;"><strong><?= esc_html($node['n']) ?></strong></td>
                        <td><?= esc_html($node['g']) ?></td>
                        <td><?= esc_html($node['d']) ?></td>
                        <td><?= esc_html($node['b']) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var map = L.map('map-lora').setView([<?= $reg[4] ?>, <?= $reg[5] ?>], <?= $reg[6] ?>);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            var nodes = <?= json_encode($nodes) ?>;
            nodes.forEach(function(n) {
                // Marker
                L.marker([n.la, n.lo]).addTo(map).bindPopup("<b>"+n.n+"</b><br>Dist: "+n.d);
                
                // Linea verso Gateway
                if (n.gla && n.glo) {
                    L.polyline([[n.la, n.lo], [n.gla, n.glo]], {
                        color: '#D327F5',
                        weight: 2,
                        opacity: 0.6
                    }).addTo(map);
                }
            });
            setTimeout(function(){ map.invalidateSize() }, 500);
        });
        </script>
        <?php return ob_get_clean();
    }
}
new NodiLoraItaliaCompleto();