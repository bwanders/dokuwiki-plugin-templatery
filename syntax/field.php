<?php
/**
 * DokuWiki Plugin Templatery (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * A field.
 */
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

        // check if we are delegating
        if($this->helper->isDelegating()) {
            return $this->helper->displayField($mode, $R, $field, $default);
        }

        // render a preview
        if($mode != 'xhtml') return false;

        $R->doc .= '<span class="templatery-field">'.$R->_xmlEntities($field);
        if($default != '') {
            $R->doc .= ' <span class="value-separator">&#187;</span> '.$R->_xmlEntities($default);
        }
        $R->doc .= '</span>';

        return true;
    }
}

