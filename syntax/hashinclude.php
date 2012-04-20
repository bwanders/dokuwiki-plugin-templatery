<?php
/**
 * DokuWiki Plugin templatery (Fake header)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

if (!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class syntax_plugin_templatery_hashinclude extends DokuWiki_Syntax_Plugin {
    function __construct() {
        $this->helper =& plugin_load('helper','templatery');
    }

    function getType() {
        // invented new type
        return 'templated';
    }

    function getSort() {
        return 290;
    }

    function getPType() {
        return 'block';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('@@#include +.+?@@', $mode, 'plugin_templatery_hashinclude');
    }

    function handle($match, $state, $pos, &$handler) {
        preg_match('/@@#include +(.+?)@@/', $match, $capture);
        return $capture[1];
    }

    function render($mode, &$R, $data) {
         // check if we are delegating
        if($this->helper->isDelegating()) {
            // FIXME: Do stuff
            return false;
        }

        // render a preview
        if($mode != 'xhtml') return false;

        $R->doc .= '<p class="templatery-hashinclude"><span>&#8249;#include '.$R->_xmlEntities($data);
        $R->doc .= '&#8250;</span></p>';

        return true;
    }
}
