<?php
/**
 * DokuWiki Plugin templatery (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class helper_plugin_templatery extends DokuWiki_Plugin {
    /**
     * Loads a template.
     * 
     * @param page string the unresolved page id
     * @param handler object the current handler
     * @return an array of instructions, or null if the template could not be made available
     */
    public function loadTemplate($page, &$handler){
        // use configured namespace as resolve base for template finding
        resolve_pageid(cleanID($this->getConf('template_namespace')), $page, $exists);

        // load template content
        $instructions = p_cached_instructions(wikiFN($page),false,$page);

        $template = array();

        // now we mangle all instructions to end up with a clean and nestable list of instructions
        $inTemplate = false;
        for($i=0;$i<count($instructions);$i++) {
            $ins = $instructions[$i];

            // we encounter a @@template@@
            if($ins[0]=='plugin' && $ins[1][0]=='templatery_template' && $ins[1][1][0] == DOKU_LEXER_ENTER) {
                $inTemplate = true;
                continue;
            }

            // we encounter a @@/template@@
            if($ins[0]=='plugin' && $ins[1][0]=='templatery_template' && $ins[1][1][0] == DOKU_LEXER_EXIT) {
                break;
            }

            // all other instructions
            if($inTemplate) $template[]=$ins;
        }

        // return the template, if any
        return array('source'=>$page,'instructions'=>$template);
    }

    private static $delegates = array();

    /**
     * Should we delegate or preview?
     */
    public function isDelegating() {
        return count(self::$delegates) > 0;
    }

    /**
     * Renders a template.
     *
     * @param template array the template to render
     * @param delegate object the delegate to use for replacements
     * @param R object the renderer to use
     */
    public function applyTemplate(&$template, &$delegate, &$R) {
        array_push(self::$delegates, $delegate);
        $R->nest($template['instructions']);
        array_pop(self::$delegates);
    }

    /**
     * Delegate hasField.
     */
    public function hasField($field) {
        return end(self::$delegates)->hasField($field);
    }

    /**
     * Delegate getField.
     */
    public function getField($mode, &$R, $field, $default) {
        return end(self::$delegates)->getField($mode, $R, $field, $default);
    }

    /**
     * Delegate displayField.
     */
    public function displayField($mode, &$R, $field, $default) {
        return end(self::$delegates)->displayField($mode, $R, $field, $default);
    }
}

// vim:ts=4:sw=4:et:
