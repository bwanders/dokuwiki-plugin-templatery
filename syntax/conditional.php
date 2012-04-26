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
                // interpret conditional
                preg_match('/<if +?(!)?([^>]+)>/', $match, $capture);

                // create new capturer
                $capturer = new Templatery_Handler_Capture($handler->CallWriter, trim($capture[2]), $capture[1] == '!');

                // push capturer into daisychain
                $handler->CallWriter =& $capturer;
                return false;

            case DOKU_LEXER_UNMATCHED:
                // we don't care about unmatched things; just get them rendered
                $handler->_addCall('cdata', array($match), $pos);
                return false;

            case DOKU_LEXER_EXIT:
                // restore old CallWriter
                $capturer =& $handler->CallWriter;
                $handler->CallWriter =& $capturer->CallWriter;

                return array($capturer->variable, $capturer->negation, $capturer->process());
        }

        return false;
    }

    public function render($mode, &$R, $data) {
        // if we're previewing, prepend some visuals
        if(!$this->helper->isDelegating()) {
            if($mode == 'xhtml') {
                $caption = $data[1] ? 'conditional_without' : 'conditional_with';
                $R->doc .= '<div class="templatery-conditional">';
                $R->doc .= '<div><span class="templatery-if">'.sprintf($this->getLang($caption), $R->_xmlEntities($data[0])).'</span></div>';
            }
        }

        // show the content if we're previewing, or if the condition is met
        if(!$this->helper->isDelegating() || ( ($data[1] && !$this->helper->hasField($data[0])) || (!$data[1] && $this->helper->hasField($data[0])) ) ) {
            $R->nest($data[2]);
        }

        // if we're previewing, append some visuals
        if(!$this->helper->isDelegating()) {
            if($mode == 'xhtml') {
                $R->doc .= '</div>';
            }
        }

        return true;
    }
}

/**
 * A custom handler to capture the contents of the conditional.
 */
class Templatery_Handler_Capture {
    var $CallWriter;
    var $calls = array();

    function __construct(&$callWriter, $variable, $negation) {
        $this->CallWriter =& $callWriter;
        $this->variable = $variable;
        $this->negation = $negation;
    }

    function writeCall($call) {
        $this->calls[] = $call;
    }

    function writeCalls($calls) {
        $this->calls = array_merge($this->calls, $calls);
    }

    function finalise() {
        // Noop. This shouldn't be required, ever.
    }

    function process() {
        $result = array();

        // compact cdata instructions
        for($i=0;$i<count($this->calls);$i++) {
            $call = $this->calls[$i];
            $key = count($result);
            if($key && $call[0] == 'cdata' && $result[$key-1][0] == 'cdata') {
                $result[$key-1][1][0] .= $call[1][0];
            } else {
                $result[] = $call;
            }
        }

        $B = new Doku_Handler_Block();
        $result = $B->process($result);

        return $result;
    }
}

// vim:ts=4:sw=4:et:
