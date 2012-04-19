<?php
/**
 * Datatemplate plugin (fake header)
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Brend Wanders <b.wanders@xs4all.nl>
 */

if (!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class syntax_plugin_templatery_header extends DokuWiki_Syntax_Plugin {

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
        // get level and title
        $title = trim($match);
        $level = 7 - strspn($title,'=');
        if($level < 1) $level = 1;
        $title = trim($title,'=');
        $title = trim($title);


        // be a good handler, and maintain the call sequence
        if ($handler->status['section']) $handler->_addCall('section_close',array(),$pos);

        // replace this with plugin call
        $handler->addPluginCall('templatery_header', array($title,$level,$pos), $state, $pos, $match);

        // open a new section after we started with this header
        $handler->_addCall('section_open',array($level),$pos);
        $handler->status['section'] = true;

        return false;
    }

    /**
     * Fakes a header. This method is heavily copied from inc/parser/xhtml.php.
     */
    function render($mode, &$renderer, $data) {
        if ($mode == 'xhtml') {
            list($text,$level,$pos) = $data;
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
            $renderer->doc .= $renderer->_xmlEntities($text).' BREND';
            $renderer->doc .= "</a></h$level>".DOKU_LF;

            return true;
        }
        return false;
    }
}
