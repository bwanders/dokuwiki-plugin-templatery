<?php
/**
 * DokuWiki Plugin templatery (Fake section)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * Replacement for the normal sections.
 */
class syntax_plugin_templatery_section extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->helper =& plugin_load('helper','templatery');
    }

    function getType() {
        return 'substition';
    }

    function getSort() {
        return 0;
    }

    function getPType() {
        return 'normal';
    }

    function connectTo($mode) {
        //no connection
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
        return false;
    }

    function render($mode, Doku_Renderer $R, $data) {
        switch($data[0]) {
            case 'open':
                $R->section_open($data[1]);
                $this->helper->pushSection($data[1]);
                return true;

            case 'close':
                $R->section_close();
                $this->helper->popSection();
                return true;

            case 'conditional_open':
                if($this->helper->peekSection() !== false) {
                    $R->section_open($this->helper->peekSection());
                }
                return true;

            case 'conditional_close':
                if($this->helper->peekSection() !== false) {
                    $R->section_close();
                }
                return true;

            default:
                return false;
        }
    }
}
