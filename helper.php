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
     * Resolves a template identifier.
     * 
     * @param id string the template identifier
     * @param exists boolean will be set to whether the page existed or not
     * @return an array containing the resolved page and normalized template id
     */
    public function resolveTemplate($id, &$exists) {
        list($page, $hash) = explode('#',$id,2);
        if(empty($hash)) $hash = '';

        $hash = $this->cleanTemplateId($hash);

        // use configured namespace as resolve base for template finding
        resolve_pageid(cleanID($this->getConf('template_namespace')), $page, $exists);

        return array($page, $hash);
    }

    /**
     * Cleans a template identifier.
     *
     * @param id string the identifier
     * @return a cleaned identifier
     */
    public function cleanTemplateId($id) {
        return str_replace(array(':','.'),'',cleanID($id));
    }

    /**
     * Loads a template.
     * 
     * @return an array of instructions, or null if the template could not be made available
     */
    public function loadTemplate($page, $hash, $sectioning = array(false)){
        if(!page_exists($page,'',false)) {
            return null;
        }

        // load template
        $instructions = p_cached_instructions(wikiFN($page),$page);

        // fetch sectioning data
        list($section, $level) = $sectioning;

        // the result        
        $template = null;

        // now we mangle all instructions to end up with a clean and nestable list of instructions
        $inTemplate = false;
        $closedSection = false;
        for($i=0;$i<count($instructions);$i++) {
            $ins = $instructions[$i];

            // we encounter a <template>
            if($ins[0]=='plugin' && $ins[1][0]=='templatery_wrapper' && $ins[1][1][0] == DOKU_LEXER_ENTER && (empty($hash) || $ins[1][1][1] == $hash)) {
                $inTemplate = true;
                $template = array();
                continue;
            }

            // we encounter the first header while we're being included in a section
            if($inTemplate && $section && !$closedSection && $ins[0]=='plugin' && $ins[1][0]=='templatery_header') {
                // close the section
                $template[] = array('section_close',array(),$ins[2]);
                $closedSection = true;
            }

            // we encounter a </template>
            if($inTemplate && $ins[0]=='plugin' && $ins[1][0]=='templatery_wrapper' && $ins[1][1][0] == DOKU_LEXER_EXIT) {
                if($section && $closedSection) {
                    $template[] = array('section_open', array($level), $ins[2]);
                }
                break;
            }

            // all other instructions
            if($inTemplate) $template[]=$ins;
        }

        // return the template, if any
        return $template;
    }

    private static $templates = array();

    public function pushTemplate($page, $hash) {
        array_push(self::$templates, "$page#$hash");
    }

    public function popTemplate() {
        array_pop(self::$templates);
    }

    public function isTemplateAllowed($page, $hash) {
        // determine maximum value
        $max = intval($this->getConf('maximum_nesting'));

        // check depth of current nesting
        return count(self::$templates) < $max;
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
        $R->nest($template);
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
