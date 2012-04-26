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

class syntax_plugin_templatery_conditional extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->helper =& plugin_load('helper', 'templatery');
    }

    public function getType() {
        return 'container';
    }

    public function getPType() {
        return 'stack';
    }

    public function getSort() {
        return 250;
    }

    public function getAllowedTypes() {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs', 'templated');
    }

    public function accepts($mode) {
        return $mode != 'plugin_templatery_wrapper' && $mode != 'plugin_templatery_header' && $mode != 'plugin_templayer_conditional' && parent::accepts($mode);
    }

    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('<if +?!?[^>]+>(?=.*?</if>)',$mode,'plugin_templatery_conditional');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('</if>','plugin_templatery_conditional');
    }

    public function handle($match, $state, $pos, &$handler){
        switch($state) {
            case DOKU_LEXER_ENTER:
                preg_match('/<if +?(!)?([^>]+)>/', $match, $capture);
                return array('open', $capture[1], trim($capture[2]));
            case DOKU_LEXER_UNMATCHED:
                // we don't care about unmatched things; just get them rendered
                $handler->_addCall('cdata', array($match), $pos);
                return false;
            case DOKU_LEXER_EXIT:
                return array('close');
        }

        return false;
    }

    public function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;

        switch($data[0]) {
            case 'open':
                $caption = $data[1] == '!' ? 'conditional_without' : 'conditional_with';
                $R->doc .= '<div class="templatery-conditional">';
                $R->doc .= '<span class="templatery-if">'.sprintf($this->getLang($caption), $R->_xmlEntities($data[2])).'</span>';
                break;
            case 'close':
                //$R->doc .= '<p class="templatery-if"><span>&#8249;'.'/'.'&#8250;</span></p>';
                $R->doc .= '</div>';
                break;
            case 'conditional':
                $neg = $data[1] == '!';
                if(($neg && !$this->helper->hasField($data[2])) || (!$neg && $this->helper->hasField($data[2]))) {
                    $R->nest($data[3]);
                }
                break;
        }

        return true;
    }
}

// vim:ts=4:sw=4:et:
