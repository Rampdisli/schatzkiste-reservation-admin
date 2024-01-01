<?php
/**
 * Plugin Name: Schatzkiste Reservation Admin
 * Description: Ein WordPress-Plugin für die Verwaltung von Schatzkisten-Reservierungen im Admin-Bereich.
 * Version: 1.0
 * Author: Dein Name
 */

// Sicherstellen, dass es direkt von WordPress aufgerufen wird
if (!defined('ABSPATH')) {
    exit;
}

// Hinzufügen von Administrationsmenüs oder anderen Funktionen hier ...

// Beispiel: Hinzufügen eines benutzerdefinierten Administrationsmenüs unter WooCommerce
add_action('admin_menu', 'schatzkiste_reservation_admin_menu');

function schatzkiste_reservation_admin_menu() {
    // Menüpunkt unter WooCommerce einfügen
    add_submenu_page(
        'woocommerce',
        'Reservationen',
        'Reservationen',
        'manage_options',
        'schatzkiste_reservation_admin',
        'schatzkiste_reservation_admin_page'
    );
}

// Beispiel: Funktion für die benutzerdefinierte Administrationsseite
function schatzkiste_reservation_admin_page() {
    ?>
    <div class="wrap">
        <h2>Schatzkiste Reservation Admin</h2>
        <p>Hier kannst du die Schatzkisten-Reservierungen verwalten.</p>

        <?php
        // Abfrage für Produkte mit _reserved-Metadatum
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_reserved',
                    'value' => 'yes',
                ),
            ),
        );

        $products_query = new WP_Query($args);

        // Zeige die Tabelle der Produkte an
        if ($products_query->have_posts()) {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>Produkt</th><th>Reservierungszeitpunkt</th><th>Benutzer</th><th>Löschen</th></tr></thead>';
            echo '<tbody>';
            while ($products_query->have_posts()) {
                $products_query->the_post();

                // Holen der Metadaten
                $reservation_timestamp = get_post_meta(get_the_ID(), '_reservation_timestamp', true);
                $reservation_user_id = get_post_meta(get_the_ID(), '_reservation_user_id', true);
                $reservation_user_name = $reservation_user_id ? get_userdata($reservation_user_id)->display_name : 'Unbekannt';

                // Formatierung des Datums
                $formatted_date = date('d.m.Y H:i:s', $reservation_timestamp);

                // WordPress-Standards für Tabellenzeilenklassen
                $post_classes = get_post_class();
                $row_class = implode(' ', $post_classes);

                echo '<tr class="' . esc_attr($row_class) . '">';
                echo '<td>' . get_the_title() . '</td>';
                echo '<td>' . $formatted_date . '</td>';
                echo '<td>' . $reservation_user_name . '</td>';
                echo '<td><button class="delete-button" data-product-id="' . esc_attr(get_the_ID()) . '">Löschen</button></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            wp_reset_postdata();
        } else {
            echo '<p>Keine reservierten Produkte gefunden.</p>';
        }
        ?>

        <script>
            jQuery(document).ready(function($) {
                // Ajax-Aufruf für das Löschen
                $('.delete-button').on('click', function() {
                    var productId = $(this).data('product-id');
                    var rowElement = $(this).closest('tr'); // Zeile der Tabelle

                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'delete_reservation',
                            product_id: productId,
                            nonce: '<?php echo wp_create_nonce('delete_reservation_nonce'); ?>'
                        },
                        success: function(response) {
                            // Ausblenden der Zeile nach erfolgreichem Löschen
                            rowElement.hide();

                            // Aktualisiere die Tabelle oder führe andere Aktionen aus
                            console.log(response);
                        },
                        error: function(error) {
                            console.error(error);
                        }
                    });
                });
            });
        </script>
    </div>
    <?php
}

// Ajax-Aktion für das Löschen registrieren
add_action('wp_ajax_delete_reservation', 'delete_reservation_callback');

function delete_reservation_callback() {
    // Überprüfe die Benutzerrolle
    $current_user = wp_get_current_user();
    if (!in_array('administrator', (array)$current_user->roles)) {
        wp_send_json_error('Du hast keine Berechtigung für diese Aktion.');
    }

    // Überprüfe die Ajax-Sicherheit
    check_ajax_referer('delete_reservation_nonce', 'nonce');

    // Holen der Produkt-ID aus dem Ajax-Anruf
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    // Löschen der Metadaten
    delete_post_meta($product_id, '_reserved');
    delete_post_meta($product_id, '_reservation_timestamp');
    delete_post_meta($product_id, '_reservation_user_id');

    wp_send_json_success('Reservierung erfolgreich gelöscht.');
}
