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

class syntax_plugin_templatery_peek extends DokuWiki_Syntax_Plugin {
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
        $this->Lexer->addSpecialPattern('\{\{peek>[^}]*?}}',$mode,'plugin_templatery_peek');
    }

    public function handle($match, $state, $pos, &$handler){
        global $ID;

        preg_match('/\{\{peek>([^\}]+)}}/',$match,$capture);
        $page = $capture[1];

        resolve_pageid(getNS($ID),$page,$exists);
        $data = p_cached_instructions(wikiFN($page));

        return $data;
    }

    public function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;

        $R->code(print_r($data,1));

        return true;
    }
}

// vim:ts=4:sw=4:et:
