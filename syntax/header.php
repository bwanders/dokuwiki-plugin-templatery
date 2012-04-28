<?php
/**
 * DokuWiki Plugin templatery (Fake header)
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

/**
 * Replacement header to ensure compatibility when
 * mucking around sections, also fixes the TOC.
 */
class syntax_plugin_templatery_header extends DokuWiki_Syntax_Plugin {
    function __construct() {
        $this->helper =& plugin_load('helper','templatery');
    }

    function getType() {
        // invented new type
        return 'templated';
    }

    function getSort() {
        return 49;
    }

    function getPType() {
        return 'block';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('[ \t]*={2,}[^\n]+={2,}[ \t]*(?=\n)', $mode, 'plugin_templatery_header');
    }

    function handle($match, $state, $pos, &$handler) {
        // code joinked from handler.php

        // get level and title
        $title = trim($match);
        $level = 7 - strspn($title,'=');
        if($level < 1) $level = 1;
        $title = trim($title,'=');
        $title = trim($title);


        // be a good handler, and maintain the call sequence
        if ($handler->status['section']) $handler->_addCall('section_close',array(),$pos);

        // output a plugin call to this plugin, instead of to the normal header
        $result = preg_split('/(@@.*?@@)/msS', $title, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $instructions = array();
        foreach($result as $r) {
            if(preg_match('/@@(.+?)(?:\|(.+?))?@@/msS',$r,$capture)) {
                $instructions[] = array('field', $capture[1], $capture[2]);
            } else {
                $instructions[] = array('text', $r);
            }
        }

        $handler->addPluginCall('templatery_header', array($title,$level,$instructions), $state, $pos, $match);

        // open a new section after we started with this header
        $handler->_addCall('section_open',array($level),$pos);
        $handler->status['section'] = true;

        // de not output a plugin instruction
        return false;
    }

    /**
     * Fakes a header. This method is heavily copied from inc/parser/xhtml.php.
     */
    function render($mode, &$R, $data) {
        list($text,$level,$instructions) = $data;

        // convert all instructions to text
        if($this->helper->isDelegating()) {
            $text = '';
            foreach($instructions as $ins) {
                switch($ins[0]) {
                    case 'text':
                        $text .= $ins[1];
                        break;

                    case 'field':
                        $text .= $this->helper->getField($mode, $R, $ins[1], $ins[2]);
                        break;
                }
            }
        }

        // render the header to XHTML
        if ($mode == 'xhtml') {
        
            if(!$text) return;
            
            $hid = $R->_headerToLink($text,true);

            // only add items within the configured levels
            $R->toc_additem($hid,$text,$level);

            // adjust $node to reflect hierarchy of levels
            $R->node[$level-1]++;
            if ($level < $R->lastlevel) {
                for ($i = 0; $i < $R->lastlevel-$level; $i++) {
                    $R->node[$R->lastlevel-$i-1] = 0;
                }
            }
            $R->lastlevel = $level;

            // write the header
            $R->doc .= DOKU_LF.'<h'.$level;
            $R->doc .= '><a name="'.$hid.'" id="'.$hid.'">';
            // $R->doc .= $R->_xmlEntities($text);
            foreach($instructions as $ins) {
                $text = $ins[1];
                switch($ins[0]) {
                    case 'text':
                        $R->doc .= $R->_xmlEntities($text);
                        break;

                    case 'field':
                        if($this->helper->isDelegating()) {
                            $this->helper->displayField($mode, $R, $ins[1], $ins[2]);
                        } else {
                            $R->doc .= '<span class="templatery-field">&#8249;'.$R->_xmlEntities($text);
                            if($ins[2]) {
                                $R->doc .= '&#187;'.$R->_xmlEntities($ins[2]);
                            }
                            $R->doc .= '&#8250;</span>';
                        }
                        break;
                }
            }
            $R->doc .= "</a></h$level>".DOKU_LF;

            return true;
        } elseif($mode == 'metadata') {
            $R->header($text, $level, null);
            return true;
        }
        return false;
    }
}
