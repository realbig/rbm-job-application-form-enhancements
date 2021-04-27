<?php
/**
 * Plugin Name: RBM Job Application Form Enhancements
 * Plugin URI: https://github.com/realbig/rbm-job-application-form-enhancements
 * Description: Auto-populates some data in the Job Application form
 * Version: 1.0.0
 * Text Domain: rbm-job-application-form-enhancements
 * Author: Real Big Marketing
 * Author URI: https://realbigmarketing.com/
 * Contributors: d4mation
 * GitHub Plugin URI: https://github.com/realbig/rbm-job-application-form-enhancements
 * GitHub Branch: master
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'RBM_Job_Application_Form_Enhancements' ) ) {

    /**
     * Main RBM_Job_Application_Form_Enhancements class
     *
     * @since      1.0.0
     */
    final class RBM_Job_Application_Form_Enhancements {
        
        /**
         * @var          array $plugin_data Holds Plugin Header Info
         * @since        1.0.0
         */
        public $plugin_data;
        
        /**
         * @var          array $admin_errors Stores all our Admin Errors to fire at once
         * @since        1.0.0
         */
        private $admin_errors = array();

        /**
         * Get active instance
         *
         * @access     public
         * @since      1.0.0
         * @return     object self::$instance The one true RBM_Job_Application_Form_Enhancements
         */
        public static function instance() {
            
            static $instance = null;
            
            if ( null === $instance ) {
                $instance = new static();
            }
            
            return $instance;

        }
        
        protected function __construct() {
            
            $this->setup_constants();
            $this->load_textdomain();
            
            if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
                
                $this->admin_errors[] = sprintf( _x( '%s requires v%s of %sWordPress%s or higher to be installed!', 'First string is the plugin name, followed by the required WordPress version and then the anchor tag for a link to the Update screen.', 'rbm-job-application-form-enhancements' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>', '</strong></a>' );
                
                if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
                    add_action( 'admin_notices', array( $this, 'admin_errors' ) );
                }
                
                return false;
                
            }
            
            $this->require_necessities();
            
            // Register our CSS/JS for the whole plugin
            add_action( 'init', array( $this, 'register_scripts' ) );

            add_filter( 'gform_pre_render_2', array( $this, 'populate_jobs_list' ), 10, 3 );
            
        }

        /**
         * Setup plugin constants
         *
         * @access     private
         * @since      1.0.0
         * @return     void
         */
        private function setup_constants() {
            
            // WP Loads things so weird. I really want this function.
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }
            
            // Only call this once, accessible always
            $this->plugin_data = get_plugin_data( __FILE__ );

            if ( ! defined( 'RBM_Job_Application_Form_Enhancements_VER' ) ) {
                // Plugin version
                define( 'RBM_Job_Application_Form_Enhancements_VER', $this->plugin_data['Version'] );
            }

            if ( ! defined( 'RBM_Job_Application_Form_Enhancements_DIR' ) ) {
                // Plugin path
                define( 'RBM_Job_Application_Form_Enhancements_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
            }

            if ( ! defined( 'RBM_Job_Application_Form_Enhancements_URL' ) ) {
                // Plugin URL
                define( 'RBM_Job_Application_Form_Enhancements_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
            }
            
            if ( ! defined( 'RBM_Job_Application_Form_Enhancements_FILE' ) ) {
                // Plugin File
                define( 'RBM_Job_Application_Form_Enhancements_FILE', __FILE__ );
            }

        }

        /**
         * Internationalization
         *
         * @access     private 
         * @since      1.0.0
         * @return     void
         */
        private function load_textdomain() {

            // Set filter for language directory
            $lang_dir = trailingslashit( RBM_Job_Application_Form_Enhancements_DIR ) . 'languages/';
            $lang_dir = apply_filters( 'rbm_job_application_form_enhancements_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'rbm-job-application-form-enhancements' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'rbm-job-application-form-enhancements', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = trailingslashit( WP_LANG_DIR ) . 'rbm-job-application-form-enhancements/' . $mofile;

            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/rbm-job-application-form-enhancements/ folder
                // This way translations can be overridden via the Theme/Child Theme
                load_textdomain( 'rbm-job-application-form-enhancements', $mofile_global );
            }
            else if ( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/rbm-job-application-form-enhancements/languages/ folder
                load_textdomain( 'rbm-job-application-form-enhancements', $mofile_local );
            }
            else {
                // Load the default language files
                load_plugin_textdomain( 'rbm-job-application-form-enhancements', false, $lang_dir );
            }

        }
        
        /**
         * Include different aspects of the Plugin
         * 
         * @access     private
         * @since      1.0.0
         * @return     void
         */
        private function require_necessities() {
            
        }

        /**
         * Populates the Jobs list in the Job Application form
         * The built-in way to do this in Gravity Forms passes a Post ID to the form which isn't useful for humans
         * Private Jobs will show if an Admin is logged in
         * 
         * @param   array   $form         The current form to be filtered
         * @param	boolean $ajax         Is AJAX enabled
         * @param	array   $field_values An array of dynamic population parameter keys with their corresponding values to be populated
         *                                                                 * 
         * @access  public
         * @since	1.0.0
         * @return	array   Modified Form
         */
        public function populate_jobs_list( $form, $ajax, $field_values ) {

            global $post;

            foreach ( $form['fields'] as &$field ) {

                if ( $field->adminLabel == 'Job' ) {

                    $jobs = new WP_Query( array(
                        'post_type' => 'jobs',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC',
                        'post_status' => 'publish',
                    ) );

                    if ( $jobs->have_posts() ) {

                        // Hyphens to emdashes in Titles
                        remove_filter( 'the_title', 'wptexturize' );
                        
                        $field->choices = array();

                        while ( $jobs->have_posts() ) : $jobs->the_post();

                            
                            $field->choices[] = array(
                                'text' => get_the_title(),
                                'value' => get_the_title(),
                                'isSelected' => ( isset( $_GET['job'] ) && urldecode( $_GET['job'] ) == get_the_title() ) ? true : false,
                                'price' => '',
                            );


                        endwhile;

                        add_filter( 'the_title', 'wptexturize' );
                        
                        wp_reset_postdata();

                    }
                    else {

                        $field->choices = array();

                        $field->choices[] = array(
                            'text' => __( 'No job openings currently available', 'rbm-job-application-form-enhancements', ),
                            'value' => '',
                            'isSelected' => true,
                            'price' => '',
                        );

                    }

                    break;

                }

            }

            return $form;

        }
        
        /**
         * Show admin errors.
         * 
         * @access     public
         * @since      1.0.0
         * @return     HTML
         */
        public function admin_errors() {
            ?>
            <div class="error">
                <?php foreach ( $this->admin_errors as $notice ) : ?>
                    <p>
                        <?php echo $notice; ?>
                    </p>
                <?php endforeach; ?>
            </div>
            <?php
        }
        
        /**
         * Register our CSS/JS to use later
         * 
         * @access     public
         * @since      1.0.0
         * @return     void
         */
        public function register_scripts() {
            
            wp_register_style(
                'rbm-job-application-form-enhancements',
                RBM_Job_Application_Form_Enhancements_URL . 'dist/assets/css/app.css',
                null,
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Job_Application_Form_Enhancements_VER
            );
            
            wp_register_script(
                'rbm-job-application-form-enhancements',
                RBM_Job_Application_Form_Enhancements_URL . 'dist/assets/js/app.js',
                array( 'jquery' ),
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Job_Application_Form_Enhancements_VER,
                true
            );
            
            wp_localize_script( 
                'rbm-job-application-form-enhancements',
                'rBMJobApplicationFormEnhancements',
                apply_filters( 'rbm_job_application_form_enhancements_localize_script', array() )
            );
            
            wp_register_style(
                'rbm-job-application-form-enhancements-admin',
                RBM_Job_Application_Form_Enhancements_URL . 'dist/assets/css/admin.css',
                null,
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Job_Application_Form_Enhancements_VER
            );
            
            wp_register_script(
                'rbm-job-application-form-enhancements-admin',
                RBM_Job_Application_Form_Enhancements_URL . 'dist/assets/js/admin.js',
                array( 'jquery' ),
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Job_Application_Form_Enhancements_VER,
                true
            );
            
            wp_localize_script( 
                'rbm-job-application-form-enhancements-admin',
                'rBMJobApplicationFormEnhancements',
                apply_filters( 'rbm_job_application_form_enhancements_localize_admin_script', array() )
            );
            
        }
        
    }
    
} // End Class Exists Check

/**
 * The main function responsible for returning the one true RBM_Job_Application_Form_Enhancements
 * instance to functions everywhere
 *
 * @since      1.0.0
 * @return     \RBM_Job_Application_Form_Enhancements The one true RBM_Job_Application_Form_Enhancements
 */
add_action( 'plugins_loaded', 'rbm_job_application_form_enhancements_load' );
function rbm_job_application_form_enhancements_load() {

    require_once trailingslashit( __DIR__ ) . 'core/rbm-job-application-form-enhancements-functions.php';
    RBMJOBAPPLICATIONFORMENHANCEMENTS();

}