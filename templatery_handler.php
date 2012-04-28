<?php
/**
 * DokuWiki Plugin Templatery (Delegate interface)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * The templatery handler.
 */
interface templatery_handler {
    /**
     * Determine whether we have a field.
     *
     * @param field string the field
     * @return true if the field is available, false otherwise
     */
    public function hasField($field);

    /**
     * Retrieve a textual representation of a field.
     * 
     * @param mode string the rendering mode
     * @param R object the renderer
     * @param field string the field to render
     * @param default string the default value (null or the empty string signifies no default value)
     * @return the value of the field, or null
     */
    public function getField($mode, &$R, $field);

    /**
     * render a field.
     * 
     * @param mode string the rendering mode
     * @param R object the renderer
     * @param field string the field to render
     * @param default string the default value (null or the empty string signifies no default value)
     * @return true if we rendered for this mode, false otherwise
     */
    public function displayField($mode, &$R, $field, $default);
}
