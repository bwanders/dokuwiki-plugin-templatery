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

class syntax_plugin_templatery_wrapper extends DokuWiki_Syntax_Plugin {
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
        return $mode != 'plugin_templatery_wrapper' && parent::accepts($mode);
    }


    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('@@template@@(?=.*?@@/template@@)',$mode,'plugin_templatery_wrapper');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('@@/template@@','plugin_templatery_wrapper');
    }

    public function handle($match, $state, $pos, &$handler){
        switch($state) {
            case DOKU_LEXER_ENTER:
                if ($handler->status['section']) {
                    $handler->_addCall('section_close',array(),$pos);
                    $handler->status['section'] = false;
                }
                return array($state);
            case DOKU_LEXER_UNMATCHED:
                // we don't care about unmatched things; just get them rendered
                $handler->_addCall('cdata', array($match), $pos);
                return false;
            case DOKU_LEXER_EXIT:
                if ($handler->status['section']) {
                    $handler->_addCall('section_close',array(),$pos);
                    $handler->status['section'] = false;
                }
                return array($state);
        }

        return array();
    }

    public function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;

        switch($data[0]) {
            case DOKU_LEXER_ENTER:
                $R->doc .= '<div class="templatery-wrapper">';
                break;
            case DOKU_LEXER_EXIT:
                $R->doc .= '</div>';
                break;
        }


        return true;
    }
}

// vim:ts=4:sw=4:et:
