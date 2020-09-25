<?php
/**
 * Provides helper functions.
 *
 * @since   {{VERSION}}
 *
 * @package RBM_Job_Application_Form_Enhancements
 * @subpackage RBM_Job_Application_Form_Enhancements/core
 */
if ( ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Returns the main plugin object
 *
 * @since   {{VERSION}}
 *
 * @return  RBM_Job_Application_Form_Enhancements
 */
function RBMJOBAPPLICATIONFORMENHANCEMENTS() {
    return RBM_Job_Application_Form_Enhancements::instance();
}