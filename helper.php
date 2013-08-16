<?php
/**
 * DokuWiki Plugin templatery (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

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
     * Determines the current sectioning information.
     */
    public function getSectioning(&$handler) {
        $section = $handler->status['section'];
        if($section) {
            // determine the level of the section
            for($i=count($handler->calls); $i --> 0 ;) {
                if($handler->calls[$i][0]=='section_open') {
                    $level = $handler->calls[$i][1][0];
                    break;
                }
            }
        }

        return array($section, $level);
    }

    /**
     * Prepares a template for rendering. This takes care of metadata
     * renering as well.
     * 
     * @param mode string the rendering mode
     * @param R object the renderer
     * @param page string the page id of the template to prepare
     * @param hash string the template name
     * @param error string an error string to return
     * @return the prepared template.
     */
    public function prepareTemplate($mode, &$R, $page, $hash, &$error) {
        if($mode == 'metadata') {
            // add reference for backlinks
            $R->meta['relation']['references'][$page] = $exists;

            // add page to list for cache handling
            if(!isset($R->meta['plugin_templatery'])) {
                $R->meta['plugin_templatery'] = array(
                    'all'=>array(),
                    'actual'=>array()
                );
            }
            $R->meta['plugin_templatery']['all'][] = $page;
        }

        // check for permission
        if (auth_quickaclcheck($page) < AUTH_READ) {
            $error = 'template_unavailable';
        }

        // add the page to the list of actual pages if it is readable
        if($mode == 'metadata' && !isset($error)) {
            $R->meta['plugin_templatery']['actual'][] = $page;
        }

        // load the template
        if(!isset($error)) {
            $template = $this->loadTemplate($page, $hash);
            if($template == null) {
                $error = 'template_nonexistant';
            }
        }

        return $template;
    }

    /**
     * Cross-instance cache.
     *
     * This cache will contain instructions and parsed templates in the following structure:
     *  [$ID]
     *       ['instructions'][$page] = parsed instructions for this page (so no double parse)
     *       ['templates']["$page#$hash"] = parsed template for this key
     */
    static $cache = array();

    /**
     * Loads a template.
     * 
     * @return an array of instructions, or null if the template could not be made available
     */
    public function loadTemplate($page, $hash) {
        global $ID;

        if(!page_exists($page,'',false)) {
            return null;
        }

        // load template (reparse to get weird plugins that use e.g. $ID working)
        // set up cache to prevent reparsing a page with multiple templates on it
        if(!isset(self::$cache[$ID]['instructions'])) {
            self::$cache[$ID]['instructions'] = array();
            self::$cache[$ID]['templates'] = array();
        }
        if(!isset(self::$cache[$ID]['instructions'][$page])) {
            self::$cache[$ID]['instructions'][$page] = p_get_instructions(io_readWikiPage(wikiFN($page),$page));
        }

        $instructions =& self::$cache[$ID]['instructions'][$page];

        $cacheKey = "$page#";
        if(!empty($hash)) $cacheKey .= $hash;

        // now we mangle all instructions to end up with a clean and nestable list of instructions
        if(!isset(self::$cache[$ID]['templates'][$cacheKey])) {
            self::$cache[$ID]['templates'][$cacheKey] = array();
            // the result
            $template =& self::$cache[$ID]['templates'][$cacheKey];

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
                if($inTemplate && !$closedSection && $ins[0]=='plugin' && $ins[1][0]=='templatery_header') {
                    // close the section
                    $template[] = array('plugin',array('templatery_section',array('conditional_close'),0,''),$ins[2]);
                    $closedSection = true;
                }
    
                // we encounter a </template>
                if($inTemplate && $ins[0]=='plugin' && $ins[1][0]=='templatery_wrapper' && $ins[1][1][0] == DOKU_LEXER_EXIT) {
                    if($closedSection) {
                        $template[] = array('plugin',array('templatery_section',array('conditional_open'),0,''),$ins[2]);
                    }
                    break;
                }
    
                // all other instructions
                if($inTemplate) {
                    switch($ins[0]) {
                        // replace section_close and section_open with templatery-aware versions
                        case 'section_open': $template[] = array('plugin',array('templatery_section',array('open',$ins[1][0]),0,''),$ins[2]); break;
    
                        case 'section_close': $template[] = array('plugin',array('templatery_section',array('close'),0,''),$ins[2]); break;
    
                        // any other instruction goes straight into the list
                        default: $template[] = $ins; break;
                    }
                }
            }
        }

        // return the template, if any
        return self::$cache[$ID]['templates'][$cacheKey];
    }

    /**
     * Stack of opened templates.
     */
    private static $templates = array();

    /**
     * Pushes a new template onto the stack.
     */
    public function pushTemplate($page, $hash) {
        array_push(self::$templates, "$page#$hash");
    }

    /**
     * Pops a template from the stack.
     */
    public function popTemplate() {
        array_pop(self::$templates);
    }

    /**
     * Checks whether the given template can be opened. This
     * takes into account the maximum recursion depth, and
     * allows for self-recursive templates.
     */
    public function isTemplateAllowed($page, $hash) {
        // determine maximum value
        $max = intval($this->getConf('maximum_nesting'));

        // check depth of current nesting
        return count(self::$templates) < $max;
    }

    /**
     * The stack of value delegates.
     */
    private static $delegates = array();

    /**
     * Should we delegate or preview?
     */
    public function isDelegating() {
        return count(self::$delegates) > 0;
    }
    
    /**
     * Gets the idx'th stack delegate from stack top.
     */
    public function getDelegate($idx=0) {
        $idx = count(self::$delegates)-1-$idx;

        return isset(self::$delegates[$idx]) ? self::$delegates[$idx] : null;
    }

    /**
     * Renders a template.
     */
    public function renderTemplate($mode, &$R, &$template, $id, $page, $hash, $sectioning, &$handler, &$error) {
        if(!isset($error) && !$this->isTemplateAllowed($page, $hash)) {
            $error = 'recursive_templates';
        }

        // render errors as messages
        if(isset($error)) {
            if($mode == 'xhtml') {
                msg(sprintf($this->getLang($error),$id),-1);
                $R->doc .= '<div class="error">';
                $R->internallink($page,sprintf($this->getLang($error),$id));
                $R->doc .= '</div>';
            }
        } else {
            // display template
            $this->pushTemplate($page, $hash);
            list($section, $level) = $sectioning;
            if($section) $this->pushSection($level);
            $this->applyTemplate($template, $handler, $R);
            if($section) $this->popSection();
            $this->popTemplate();
        }
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
     * Delegate listFields.
     */
    public function listFields() {
        return $this->getDelegate()->listFields();
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

    /**
     * The section stack. This stack is used for the
     * on the fly fixing of sections within templates.
     */
    private static $sections = array();

    /**
     * Pushes a section onto the stack.
     */
    public function pushSection($level) {
        array_push(self::$sections,$level);
    }

    /**
     * Pops a section from the stack.
     */
    public function popSection() {
        array_pop(self::$sections);
    }

    /**
     * Peeks at the current top section.
     */
    public function peekSection() {
        return end(self::$sections);
    }
}

