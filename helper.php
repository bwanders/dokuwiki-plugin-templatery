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
    private static $opened = array();

    public function cleanTemplateId($id) {
        return str_replace(array(':','.'),'',cleanID($id));
    }

    /**
     * Loads a template.
     * 
     * @param page string the unresolved page id
     * @param handler object the current handler
     * @return an array of instructions, or null if the template could not be made available
     */
    public function loadTemplate($page, &$handler){
        list($page, $hash) = explode('#',$page,2);
        if(empty($hash)) $hash = '';

        $hash = $this->cleanTemplateId($hash);

        // use configured namespace as resolve base for template finding
        resolve_pageid(cleanID($this->getConf('template_namespace')), $page, $exists);

        $result = array(
            'source' => $page,
            'instructions' => null
        );

        // check availability
        if(!$exists) {
            $result['error'] = 'template_nonexistant';
            return $result;
        }

        // check recursion
        if(in_array($page, self::$opened)) {
            $result['error'] = 'recursive_templates';
            return $result;
        }

        // load template
        array_push(self::$opened, $page);
        $instructions = p_get_instructions(io_readWikiPage(wikiFN($page),$page));
        array_pop(self::$opened);

        $template = false;

        // now we mangle all instructions to end up with a clean and nestable list of instructions
        $inTemplate = false;
        for($i=0;$i<count($instructions);$i++) {
            $ins = $instructions[$i];

            // we encounter a @@template@@
            if($ins[0]=='plugin' && $ins[1][0]=='templatery_wrapper' && $ins[1][1][0] == DOKU_LEXER_ENTER && (empty($hash) || $ins[1][1][1] == $hash)) {
                $inTemplate = true;
                $template = array();
                continue;
            }

            // we encounter a @@/template@@
            if($ins[0]=='plugin' && $ins[1][0]=='templatery_wrapper' && $ins[1][1][0] == DOKU_LEXER_EXIT && $inTemplate) {
                break;
            }

            // all other instructions
            if($inTemplate) $template[]=$ins;
        }

        if($template === false) {
            $result['error'] = 'template_nonexistant';
            return $result;
        }

        // return the template, if any
        $result['instructions'] = $template;
        return $result;
    }

    private static $delegates = array();

    /**
     * Should we delegate or preview?
     */
    public function isDelegating() {
        return count(self::$delegates) > 0;
    }

    public function getDelegate($idx=0) {
        $idx = count(self::$delegates)-1-$idx;

        return isset(self::$delegates[$idx]) ? self::$delegates[$idx] : null;
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
        return $this->getDelegate()->hasField($field);
    }

    /**
     * Delegate getField.
     */
    public function getField($mode, &$R, $field, $default) {
        return $this->getDelegate()->getField($mode, $R, $field, $default);
    }

    /**
     * Delegate displayField.
     */
    public function displayField($mode, &$R, $field, $default) {
        return $this->getDelegate()->displayField($mode, $R, $field, $default);
    }
}

// vim:ts=4:sw=4:et:
