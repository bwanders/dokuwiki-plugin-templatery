<?php
/**
 * DokuWiki Plugin skeleton (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_templatery_field extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->helper =& plugin_load('helper', 'templatery');
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'normal';
    }

    public function getSort() {
        return 300;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('@@.+?@@',$mode,'plugin_templatery_field');
    }

    public function handle($match, $state, $pos, &$handler){
        global $ID;

        preg_match('/@@(.+?)(?:\|(.+?))?@@/msS',$match,$capture);

        return array($capture[1], $capture[2]);
    }

    public function render($mode, &$R, $data) {
        list($field, $default) = $data;

        if($this->helper->delegate('field', $mode, $R, $field)) return true;

        if($mode != 'xhtml') return false;

        $R->doc .= '<span style="background-color: silver; border-radius: 2px; padding-left: 0.2em; padding-right:0.2em">&#8249;'.$R->_xmlEntities($field);
        if($default) {
            $R->doc .= '&#187;'.$R->_xmlEntities($default);
        }
        $R->doc .= '&#8250;</span>';

        return true;
    }
}

// vim:ts=4:sw=4:et:
