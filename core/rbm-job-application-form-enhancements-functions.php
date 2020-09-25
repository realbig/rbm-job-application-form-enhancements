<?php
/**
 * Provides helper functions.
 *
 * @since   1.0.0
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
 * @since   1.0.0
 *
 * @return  RBM_Job_Application_Form_Enhancements
 */
function RBMJOBAPPLICATIONFORMENHANCEMENTS() {
    return RBM_Job_Application_Form_Enhancements::instance();
}