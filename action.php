<?php
/**
 * DokuWiki Plugin Templatery (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * This plugin takes care of the cache check.
 */
class action_plugin_templatery extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_check_cache');
    }

    public function _check_cache(&$event, $param) {
        $cache =& $event->data;

        // ignore cache requests for things we don't influence
        if(!isset($cache->page) || !in_array($cache->mode, array('xhtml','metadata'))) return;

        // retrieve metadata on the page we're rendering
        $pages = p_get_metadata($cache->page, 'plugin_templatery');

        // no metadata -> ignore
        if(!is_array($pages)) return;

        // check change in actual list
        $actual = array();
        foreach($pages['all'] as $p) {
            if(auth_quickaclcheck($p) >= AUTH_READ) $actual[] = $p;
        }

        // If list of included pages has changed, purge
        if($pages['actual'] != $actual) {
            $cache->depends['purge'] = true;
        }

        // Depend on all actually included pages, so we get updated if the template changes
        $cache->depends['files'] = array_merge($cache->depends['files'], array_map('wikiFN',$pages['actual']));
    }
}
