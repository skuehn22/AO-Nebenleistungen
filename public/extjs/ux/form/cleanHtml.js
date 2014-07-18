/**
 * säubern der Inhalte eines HtmlEditor.
 * Entfernt Steuerzeichen der Worddokumente
 *
 *
 *
 *
 *
 */

var cleanHtmlEditor = function(){
    // beginn private


    // ende private
    // beginn public
    return{
        start: function(idHtmlEditor){
            return this.syncValue(idHtmlEditor);
        },
        dirtyHtmlTags: [
            // http://stackoverflow.com/questions/2875027/clean-microsoft-word-pasted-text-using-javascript
            // http://stackoverflow.com/questions/1068280/javascript-regex-multiline-flag-doesnt-work
            {regex: /<!--[\s\S]*?-->/gi, replaceVal: ""},

            // http://www.1stclassmedia.co.uk/developers/clean-ms-word-formatting.php
            {regex: /<\\?\?xml[^>]*>/gi, replaceVal: ""},
            {regex: /<\/?\w+:[^>]*>/gi, replaceVal: ""}, // e.g. <o:p...

            {regex: /\s*MSO[-:][^;"']*/gi, replaceVal: ""},
            {regex: /\s*MARGIN[-:][^;"']*/gi, replaceVal: ""},
            {regex: /\s*PAGE[-:][^;"']*/gi, replaceVal: ""},
            {regex: /\s*TAB[-:][^;"']*/gi, replaceVal: ""},
            {regex: /\s*LINE[-:][^;"']*/gi, replaceVal: ""},
            {regex: /\s*FONT-SIZE[^;"']*/gi, replaceVal: ""},
            {regex: /\s*LANG=(["'])[^"']*?\1/gi, replaceVal: ""},
            {regex: /<(P|H\d)[^>]*>([\s\S]*?)<\/\1>/gi, replaceVal: "$2"},

            {regex: /\s*\w+=(["'])((&nbsp;|\s|;)*|\s*;+[^"']*?|[^"']*?;{2,})\1/gi, replaceVal: ""},
            {regex: /<span[^>]*>(&nbsp;|\s)*<\/span>/gi, replaceVal: ""},
            //{regex: /<([^\s>]+)[^>]*>(&nbsp;|\s)*<\/\1>/gi, replaceVal: ""},

            // http://www.codinghorror.com/blog/2006/01/cleaning-words-nasty-html.html
            {regex: /<(\/?title|\/?meta|\/?style|\/?st\d|\/?head|\/?html|\/?body|!\[)[^>]*?>/gi, replaceVal: ""},
            {regex: /(\n(\r)?){2,}/gi, replaceVal: ""}
        ],
        syncValue : function(idHtmlEditor){
            var htmleditor = Ext.getCmp(idHtmlEditor);
            var bd = htmleditor.getEditorBody();
            var html = bd.innerHTML;

            if (this.hasDirtyHtmlTags(html)){
                // Note: the selection will be lost...
                bd.innerHTML = this.cleanHtml(html);
                if(Ext.isGecko){
                    // Gecko hack, see: https://bugzilla.mozilla.org/show_bug.cgi?id=232791#c8
                    this.setDesignMode(false); //toggle off first
                    this.setDesignMode(true);
                }
                return true;
            }
            return true;
        },
        hasDirtyHtmlTags: function(html){
            if (!html) return;

            var hasDirtyHtmlTags = false;
            Ext.each(this.dirtyHtmlTags, function(tag, idx){
                return !(hasDirtyHtmlTags = html.match(tag.regex));
            });
            return hasDirtyHtmlTags;
        },
        cleanHtml: function(html) {
            if (!html) return;

            Ext.each(this.dirtyHtmlTags, function(tag, idx){
                html = html.replace(tag.regex, tag.replaceVal);
            });

            // http://www.tim-jarrett.com/labs_javascript_scrub_word.php
            html = html.replace(new RegExp(String.fromCharCode(8220), 'gi'), '"'); //“
            html = html.replace(new RegExp(String.fromCharCode(8221), 'gi'), '"'); //”
            html = html.replace(new RegExp(String.fromCharCode(8216), 'gi'), "'"); //‘
            html = html.replace(new RegExp(String.fromCharCode(8217), 'gi'), "'"); //‘
            html = html.replace(new RegExp(String.fromCharCode(8211), 'gi'), "-"); //–
            html = html.replace(new RegExp(String.fromCharCode(8212), 'gi'), "--"); //—
            html = html.replace(new RegExp(String.fromCharCode(189), 'gi'), "1/2"); //½
            html = html.replace(new RegExp(String.fromCharCode(188), 'gi'), "1/4"); //¼
            html = html.replace(new RegExp(String.fromCharCode(190), 'gi'), "3/4"); //¾
            html = html.replace(new RegExp(String.fromCharCode(169), 'gi'), "(C)"); //©
            html = html.replace(new RegExp(String.fromCharCode(174), 'gi'), "(R)"); //®
            html = html.replace(new RegExp(String.fromCharCode(8230), 'gi'), "..."); //…

            return html;
        }
    }
    // ende public
}