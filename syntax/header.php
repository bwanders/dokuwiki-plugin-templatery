<?php
/**
 * DokuWiki Plugin templatery (Fake header)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

if (!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

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
            if(strpos($r,'@@')===0) {
                $instructions[] = array('field', substr($r,2,-2));
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
    function render($mode, &$renderer, $data) {
        list($text,$level,$instructions) = $data;
        if ($mode == 'xhtml') {
        
            if(!$text) return;
            
            $hid = $renderer->_headerToLink($text,true);

            // only add items within the configured levels
            $renderer->toc_additem($hid,$text,$level);

            // adjust $node to reflect hierarchy of levels
            $renderer->node[$level-1]++;
            if ($level < $renderer->lastlevel) {
                for ($i = 0; $i < $renderer->lastlevel-$level; $i++) {
                    $renderer->node[$renderer->lastlevel-$i-1] = 0;
                }
            }
            $renderer->lastlevel = $level;

            // write the header
            $renderer->doc .= DOKU_LF.'<h'.$level;
            $renderer->doc .= '><a name="'.$hid.'" id="'.$hid.'">';
            // $renderer->doc .= $renderer->_xmlEntities($text);
            foreach($instructions as $ins) {
                $text = $ins[1];
                switch($ins[0]) {
                    case 'text': $renderer->doc .= $renderer->_xmlEntities($text); break;
                    case 'field':
                    if($this->helper->isPreview()){
                        $renderer->doc.= '<span style="background-color: silver; border-radius: 2px; padding-left: 0.2em; padding-right:0.2em">&#8249;'.$renderer->_xmlEntities($text).'&#8250;</span>'; break;
                    } else {
                        $this->helper->delegate('field', $mode, $renderer, $text);
                    }
                }
            }
            $renderer->doc .= "</a></h$level>".DOKU_LF;

            return true;
        } elseif($mode == 'metadata') {
            $renderer->header($text, $level, null);
            return true;
        }
        return false;
    }
}
