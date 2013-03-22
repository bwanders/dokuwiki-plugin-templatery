<?php
/**
 * DokuWiki Plugin skeleton (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * The template wrapper for defining templates.
 */
class syntax_plugin_templatery_wrapper extends DokuWiki_Syntax_Plugin {
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
        return 5;
    }

    public function getAllowedTypes() {
        return array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs', 'templated');
    }

    public function accepts($mode) {
        return $mode != 'plugin_templatery_wrapper' && parent::accepts($mode);
    }


    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('<template[^>\n]*>(?=.*?</template>)',$mode,'plugin_templatery_wrapper');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('</template>','plugin_templatery_wrapper');
    }

    public function handle($match, $state, $pos, &$handler){
        switch($state) {
            case DOKU_LEXER_ENTER:
                // close section and store the level of the section we just closed
                if ($handler->status['section']) {
                    $handler->_addCall('section_close',array(),$pos);
                    $handler->status['section'] = false;

                    // did we put the template into a section?
                    // determine the level of the section
                    for($i=count($handler->calls); $i --> 0 ;) {
                        if($handler->calls[$i][0]=='section_open') {
                            $level = $handler->calls[$i][1][0];
                            break;
                        }
                    }

                    if(isset($level)) {
                        $this->level = $level;
                    } else {
                        unset($this->level);
                    }
                }

                // grab the name
                preg_match('/<template([^>\n]*)>/',$match,$capture);

                // output an instruction
                return array($state, $this->helper->cleanTemplateId($capture[1]), $capture[1]);

            case DOKU_LEXER_UNMATCHED:
                // we don't care about unmatched things; just get them rendered
                $handler->_addCall('cdata', array($match), $pos);
                return false;

            case DOKU_LEXER_EXIT:
                // if we were in a section due to the template, close it
                if ($handler->status['section']) {
                    $handler->_addCall('section_close',array(),$pos);
                    $handler->status['section'] = false;
                }

                $handler->addPluginCall('templatery_wrapper',array($state), $state, $pos, $match);

                // if the template interupted a section, reopen it
                if(isset($this->level)) {
                    $handler->_addCall('section_open', array($this->level), $pos);
                    $handler->status['section'] = true;
                }

                return false;
        }

        return false;
    }

    public function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;

        switch($data[0]) {
            case DOKU_LEXER_ENTER:
                // output wrapper div and label
                $R->doc .= '<div class="templatery-wrapper">';
                $R->doc .= '<div class="templatery-wrapper-header"><span class="templatery-wrapper-label">'.$R->_xmlEntities($data[2]).'</span></div>';
                break;

            // close div
            case DOKU_LEXER_EXIT:
                $R->doc .= '<div class="clearer"></div></div>';
                break;
        }


        return true;
    }
}

// vim:ts=4:sw=4:et:
