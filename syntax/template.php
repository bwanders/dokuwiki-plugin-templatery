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

class syntax_plugin_brendtemplate_template extends DokuWiki_Syntax_Plugin {
    public function getType() {
        return 'container';
    }

    public function getPType() {
        return 'stack';
    }

    public function getSort() {
        return 5;
    }

    public function getAllowedTypes() {
        return array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs', 'templated');
    }

    public function accepts($mode) {
        return $mode != 'plugin_brendtemplate_template' && parent::accepts($mode);
    }


    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('@@template@@(?=.*?@@/template@@)',$mode,'plugin_brendtemplate_template');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('@@/template@@','plugin_brendtemplate_template');
    }

    public function handle($match, $state, $pos, &$handler){
        switch($state) {
            case DOKU_LEXER_ENTER:
                return array($state);
            case DOKU_LEXER_UNMATCHED:
                return array($state, $match);
            case DOKU_LEXER_EXIT:
                return array($state);
        }

        return array();
    }

    public function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;

        switch($data[0]) {
            case DOKU_LEXER_ENTER:
                $R->doc .= '<p><span style="background-color: rgb(255,128,128); border-radius: 2px; padding-left: 0.2em; padding-right:0.2em">&#8249;template&#8250;</span></p>';
                break;
            case DOKU_LEXER_UNMATCHED:
                $R->doc .= $R->_xmlEntities($data[1]);
                break;
            case DOKU_LEXER_EXIT:
                $R->doc .= '<p><span style="background-color: rgb(255,128,128); border-radius: 2px; padding-left: 0.2em; padding-right:0.2em">&#8249;/template&#8250;</span></p>';
                break;
        }


        return true;
    }
}

// vim:ts=4:sw=4:et:
