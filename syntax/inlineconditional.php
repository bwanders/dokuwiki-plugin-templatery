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
 * The inline conditional.
 */
class syntax_plugin_templatery_inlineconditional extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->helper =& plugin_load('helper', 'templatery');
    }

    public function getType() {
        return 'formatting';
    }

    public function getPType() {
        return 'normal';
    }

    public function getSort() {
        return 251;
    }

    public function getAllowedTypes() {
        return array('formatting', 'substition', 'disabled');
    }

    public function accepts($mode) {
        return $mode != 'plugin_templatery_inlineconditional' && parent::accepts($mode);
    }

    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('<if +?!?[^>]+>(?=.*?</if>)',$mode,'plugin_templatery_inlineconditional');
        $this->Lexer->addEntryPattern('<\*if +?!?[^>]+>(?=.*?</if>)',$mode,'plugin_templatery_inlineconditional');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('</if>','plugin_templatery_inlineconditional');
    }

    public function handle($match, $state, $pos, $handler){
        switch($state) {
            case DOKU_LEXER_ENTER:
                // interpret conditional
                preg_match('/<\*?if +?(!)?([^>]+)>/', $match, $capture);

                // create new capturer
                $capturer = new Templatery_Handler_Inline_Capture($handler->CallWriter, trim($capture[2]), $capture[1] == '!');

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

    public function render($mode, $R, $data) {
        // if we're previewing, prepend some visuals
        if(!$this->helper->isDelegating()) {
            if($mode == 'xhtml') {
                $caption = $data[1] ? 'conditional_without' : 'conditional_with';
                $R->doc .= '<span class="templatery-conditional">';
                $R->doc .= '<span class="templatery-condition">'.sprintf($this->getLang($caption), $R->_xmlEntities($data[0])).'</span>';
            }
        }

        // show the content if we're previewing, or if the condition is met
        if(!$this->helper->isDelegating() || ( ($data[1] && !$this->helper->hasField($data[0])) || (!$data[1] && $this->helper->hasField($data[0])) ) ) {
            $R->nest($data[2]);
        }

        // if we're previewing, append some visuals
        if(!$this->helper->isDelegating()) {
            if($mode == 'xhtml') {
                $R->doc .= '</span>';
            }
        }

        return true;
    }
}

/**
 * A custom handler to capture the contents of the conditional.
 */
class Templatery_Handler_Inline_Capture implements Doku_Handler_CallWriter_Interface {
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

        return $result;
    }
}

