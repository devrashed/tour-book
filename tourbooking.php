<?php
/**
 * Plugin Name:       Tour booking
 * Description:       this is a plugin for tour booking.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Md.Rashed khan
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tour-booking
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


class TourBooking {
    /**
     * Constructor to initialize the plugin.
     */
    public function __construct() {
        add_action('init', array( $this, 'register_post_type' ) );
        add_action('wp_enqueue_scripts', [$this, 'enqueue_filter_script']);
        add_action('add_meta_boxes', [$this, 'tg_add_meta_boxes']);
        add_action('save_post', [$this, 'tg_save_meta_boxes']);
        add_shortcode('tour_filter_form', [$this, 'tg_render_tour_filter_form_shortcode' ]);
        register_activation_hook(__FILE__, [$this, 'create_booking_table']);
        add_action('wp_ajax_submit_tour_booking', [$this, 'handle_tour_booking_submission']);
        add_action('wp_ajax_nopriv_submit_tour_booking', [$this, 'handle_tour_booking_submission']);
    }

    public function enqueue_filter_script() {
        wp_enqueue_script('jquery');
        
        wp_enqueue_script('duration_script', plugin_dir_url(__FILE__) . 'assets/js/duration_script.js', array('jquery'), time(), true);
        wp_enqueue_script('price-range', plugin_dir_url(__FILE__) . 'assets/js/price-range.js', array('jquery'), time(), true);
        wp_enqueue_script('tour-js', plugin_dir_url(__FILE__) . 'assets/js/tour.js', array('jquery'), time(), true);
        wp_localize_script('tour-js', 'tour_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('tour_booking_nonce')
        ));
        wp_enqueue_style('tour-css', plugin_dir_url(__FILE__) . 'assets/css/tourstyle.css', array(), time(), false);
    }
    /**
     * Create the booking table on plugin activation.
     */

    public function create_booking_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'tour_bookings';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tour_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(50),
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Register the custom post type for tours.
     */
    public function register_post_type() {
        register_post_type(
            'tour',
            array(
                'labels'      => array(
                    'name'          => __( 'Tours', 'tour-booking' ),
                    'singular_name' => __( 'Tour', 'tour-booking' ),
                ),
                'public'      => true,
                'show_in_rest'=> true,
                'supports'    => array( 'title', 'editor', 'thumbnail' ),
            )
        );
    }

    public function tg_add_meta_boxes() {
        add_meta_box(
            'tour_details_meta_box',
            __( 'Tour Details', 'tour-booking' ),
            array( $this, 'tg_render_meta_box' ),
            'tour',
            'normal',
            'default'
        );
    }

    public function tg_render_meta_box( $post ) {
        // Nonce for security
        wp_nonce_field( basename( __FILE__ ), 'tour_meta_nonce' );

        // Retrieve existing values from the database
        $destination = get_post_meta( $post->ID, '_tour_destination', true );
        $price       = get_post_meta( $post->ID, '_tour_price', true );
        $duration    = get_post_meta( $post->ID, '_tour_duration', true );
        ?>

        <p>
            <label for="tour_destination"><?php _e( 'Destination', 'tour-booking' ); ?></label><br>
            <input type="text" name="tour_destination" id="tour_destination" value="<?php echo esc_attr( $destination ); ?>" class="widefat" />
        </p>

        <p>
            <label for="tour_price"><?php _e( 'Price', 'tour-booking' ); ?></label><br>
            <input type="number" name="tour_price" id="tour_price" value="<?php echo esc_attr( $price ); ?>" class="widefat" step="0.01" />
        </p>

        <p>
            <label for="tour_duration"><?php _e( 'Duration', 'tour-booking' ); ?></label><br>
            <input type="number" name="tour_duration" id="tour_duration" value="<?php echo esc_attr( $duration ); ?>" class="widefat" />
        </p>

        <?php
    }

    public function tg_save_meta_boxes ( $post_id ) {
        // Verify nonce
        if ( ! isset( $_POST['tour_meta_nonce'] ) || ! wp_verify_nonce( $_POST['tour_meta_nonce'], basename( __FILE__ ) ) ) {
            return $post_id;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check permissions
        if ( isset( $_POST['post_type'] ) && 'tour' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }

        // Save or update meta values
        $fields = array(
            '_tour_destination' => sanitize_text_field( $_POST['tour_destination'] ?? '' ),
            '_tour_price'       => sanitize_text_field( $_POST['tour_price'] ?? '' ),
            '_tour_duration'    => sanitize_text_field( $_POST['tour_duration'] ?? '' ),
        );

        foreach ( $fields as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }
    }

        public function tg_render_tour_filter_form_shortcode() {
            ob_start();
            global $wpdb;
            $meta_key = '_tour_destination';
            $destinations = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = %s",
                    $meta_key
                )
            );

            ?>

        <div class="tg_layout">

           <div class="tour-filter-form">
                <form method="GET">
                    <h4>Destinations</h4>
                    <?php
                        if ( $destinations ) {
                            foreach ( $destinations as $destination ) {
                                $checked = ( isset($_GET['destination']) && in_array($destination, $_GET['destination']) ) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html($destination) . '</label><br>';
                            }
                        } else {
                            echo '<p>No destinations found.</p>';
                        }
                    ?>

                    <h4>Duration (days)</h4>
                
                        <div id="RangeSlider" class="range-slider">
                            <div>
                                <div class="range-slider-val-left" style="width: 0%;"></div>
                                <div class="range-slider-val-right" style="width: 0%;"></div>
                                <div class="range-slider-val-range" style="left: 0%; right: 0%;"></div>
                                <span class="range-slider-handle range-slider-handle-left" style="left: 0%;"></span>
                                <span class="range-slider-handle range-slider-handle-right" style="left: 100%;"></span>
                                <div class="range-slider-tooltip range-slider-tooltip-left" style="left: 0%;">
                                    <span class="range-slider-tooltip-text">0</span>
                                </div>
                                <div class="range-slider-tooltip range-slider-tooltip-right" style="left: 100%;">
                                    <span class="range-slider-tooltip-text">100</span>
                                </div>
                            </div>
                            <input type="range" class="range-slider-input-left" tabindex="0" max="100" min="0" step="1"> 
                            <input type="range" class="range-slider-input-right" tabindex="0" max="100" min="0" step="1"> 
                        </div>

                        <div class="input-container">
                            <div class="input-group">                
                                <input type="number" name="duration_min" id="minValue" min="0" max="100" step="1" value="<?php echo esc_attr($_GET['duration_min'] ?? ''); ?>" >Days
                            </div>
                            <div class="input-group">
                                <input type="number" name="duration_max" id="maxValue" min="0" max="100" step="1" value="<?php echo esc_attr($_GET['duration_max'] ?? ''); ?>">Days
                            </div>
                        </div>
                    
                    <h4>Price (SEK)</h4>
                   
                       <div class="range-container">
                            <div class="range-slider">
                                <div class="range-track" id="rangeTrack"></div>
                            </div>
                            <input type="range" min="100" max="5000" value="100" id="minRange" class="range-input">
                            <input type="range" min="100" max="5000" value="5000" id="maxRange" class="range-input">
                        </div>
                    

                        <div class="price-display">
                            <div class="price-input-group">
                                <input type="number" id="minPriceInput" name="price_min" class="price-input" min="100" max="5000" value="<?php echo esc_attr($_GET['price_min'] ?? '100'); ?>">
                                <div class="price-label">Min Price</div>
                            </div>
                            <div class="price-input-group">
                                <input type="number" id="maxPriceInput" name="price_max" class="price-input" min="100" max="5000" value="<?php echo esc_attr($_GET['price_max'] ?? '5000'); ?>">
                                <div class="price-label">Max Price</div>
                            </div>
                        </div>

                    <br><br>
                    <input type="submit" value="Use filters">
                    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>">Clear filters</a>
                </form>
          </div> 
        
            <div class="tour-body">
                
              <?php
            // Query tours based on filters
            $meta_query = [];
            $has_filters = false;

            // Destination
            if ( !empty($_GET['destination']) && is_array($_GET['destination']) ) {
                $meta_query[] = array(
                    'key'     => '_tour_destination',
                    'value'   => $_GET['destination'],
                    'compare' => 'IN'
                );
                $has_filters = true;
            }

            // Duration
            if ( !empty($_GET['duration_min']) || !empty($_GET['duration_max']) ) {
                $meta_query[] = array(
                    'key'     => '_tour_duration',
                    'value'   => array( $_GET['duration_min'] ?? 0, $_GET['duration_max'] ?? 999 ),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
                $has_filters = true;
            }

            // Price
            if ( !empty($_GET['price_min']) || !empty($_GET['price_max']) ) {
                $meta_query[] = array(
                    'key'     => '_tour_price',
                    'value'   => array( $_GET['price_min'] ?? 0, $_GET['price_max'] ?? 999999 ),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
                $has_filters = true;
            }

            // Build query args
            $args = array(
                'post_type'      => 'tour',
                'posts_per_page' => -1,
                'post_status'    => 'publish'
            );

            // Only add meta_query if we have filters
            if ( $has_filters && !empty($meta_query) ) {
                $args['meta_query'] = $meta_query;
                
                // If multiple meta queries, set relation
                if ( count($meta_query) > 1 ) {
                    $args['meta_query']['relation'] = 'AND';
                }
            }

            // Execute the query
        if ( !isset($_GET['destination']) == '' || !isset($_GET['duration_min']) == '' || !isset($_GET['duration_max']) == '' || !isset($_GET['price_min']) == '' || !isset($_GET['price_max']) == '' ) 
         { 

            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                echo '<ul class="grid-list">';
                while ( $query->have_posts() ) {
                    $query->the_post();
                $thumbnail = get_the_post_thumbnail( get_the_ID(), 'medium' ); // you can change 'medium' to 'thumbnail' or 'full'
                echo '<li>';
                    echo $thumbnail;
                    echo '<strong><a href="#" class="tour-booking-link" data-tour-id="' . get_the_ID() . '" data-tour-title="' . esc_attr(get_the_title()) . '">';
                        echo get_the_title();
                    echo '</a></strong><br>';
                    echo esc_html( get_post_meta( get_the_ID(), '_tour_destination', true ) );
                echo '</li>';

                }
                echo '</ul>';
                wp_reset_postdata();
            } else {
                echo '<p>No tours found.</p>';
            }
            
        } else {
                     
        $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                echo '<ul class="grid-list">';
                while ( $query->have_posts() ) {
                    $query->the_post();
                $thumbnail = get_the_post_thumbnail( get_the_ID(), 'medium' ); // you can change 'medium' to 'thumbnail' or 'full'
                echo '<li>';
                    echo $thumbnail;
                    echo '<strong><a href="#" class="tour-booking-link" data-tour-id="' . get_the_ID() . '" data-tour-title="' . esc_attr(get_the_title()) . '">';
                        echo get_the_title();
                    echo '</a></strong><br>';
                    echo esc_html( get_post_meta( get_the_ID(), '_tour_destination', true ) );
                echo '</li>';

                }
                echo '</ul>';
                wp_reset_postdata();
            } else {
                echo '<p>No tours found.</p>';
            }


        }    

            ?>
                    
            </div>            
        </div>
           
            <!-- Booking Modal -->
            <div id="booking-modal" class="booking-modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h3 id="modal-tour-title">Book Tour</h3>
                    <form id="booking-form">
                        <input type="hidden" id="tour-id" name="tour_id" value="">
                        
                        <label for="customer-name">Name *</label>
                        <input type="text" id="customer-name" name="name" required>
                        
                        <label for="customer-email">Email *</label>
                        <input type="email" id="customer-email" name="email" required>
                        
                        <label for="customer-phone">Phone</label>
                        <input type="tel" id="customer-phone" name="phone">
                        
                        <label for="customer-message">Message</label>
                        <textarea id="customer-message" name="message" rows="4"></textarea>
                        
                        <button type="submit">Submit Booking</button>
                        <button type="button" class="cancel-btn">Cancel</button>
                    </form>
                    <div id="booking-result" style="display: none;"></div>
                </div>
            </div>

            <?php
            return ob_get_clean();
        }


        /**
         * Handle the tour booking form submission via AJAX.
         */
        public function handle_tour_booking_submission() {
            // Verify nonce
            check_ajax_referer('tour_booking_nonce', 'nonce');
            
            // Sanitize input data
            $tour_id = intval($_POST['tour_id']);
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone']);
            $message = sanitize_textarea_field($_POST['message']);
            
            // Validate required fields
            if (empty($tour_id) || empty($name) || empty($email)) {
                wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            }
            
            if (!is_email($email)) {
                wp_send_json_error(array('message' => 'Please enter a valid email address.'));
            }
            
            // Check if tour exists
            $tour = get_post($tour_id);
            if (!$tour || $tour->post_type !== 'tour') {
                wp_send_json_error(array('message' => 'Invalid tour selected.'));
            }
            
            // Insert booking into database
            global $wpdb;
            $table = $wpdb->prefix . 'tour_bookings';
            
            $result = $wpdb->insert(
                $table,
                array(
                    'tour_id' => $tour_id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'message' => $message,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                wp_send_json_error(array('message' => 'Failed to save booking. Please try again.'));
            }
            wp_send_json_success(array('message' => 'Booking submitted successfully! We will contact you soon.'));
        }


}
new TourBooking();


?>