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
require_once DOKU_PLUGIN.'templatery/templatery_handler.php';

class syntax_plugin_templatery_include extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->helper =& plugin_load('helper', 'templatery');
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 300;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{include>[^}]*?}}',$mode,'plugin_templatery_include');
    }

    public function handle($match, $state, $pos, &$handler){
        global $ID;

        preg_match('/\{\{include>([^\}]+)}}/',$match,$capture);
        $page = $capture[1];

        $template = $this->helper->loadTemplate($page);

        return array($page,$template);
    }

    public function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;

        list($page, $template) = $data;

        // check for permission
        if (auth_quickaclcheck($template['source']) < AUTH_READ) {
            $template = null;
        }

        if($template != null) {
            $handler = new templatery_include_handler();
            $this->helper->applyTemplate($template, $handler, $R);
        } else {
            msg('[template \''.$page.'\' not available]',-1);
            $R->internalLink($template['source'], '[template \''.$page.'\' not available]');
        }

        return true;
    }
}

class templatery_include_handler implements templatery_handler {
    public function field($mode, &$R, $field) {
        if($mode != 'xhtml') return false;

        $R->doc .= $R->_xmlEntities($field.'BREND');

        return true;
    }
}

// vim:ts=4:sw=4:et:
