/**
 * Admin JavaScript for Micromax Gerdali Video Story Shortcode Generator
 * Version: 1.6.0
 */

jQuery(document).ready(function($) {
    var $channelIdInput = $('#micromax-gerdali-channel-id');
    var $videoCountInput = $('#micromax-gerdali-video-count');
    var $shortcodeDisplay = $('#micromax-gerdali-shortcode-display');
    var $copyBtn = $('#micromax-gerdali-copy-btn');
    var copyTimeout;

    /**
     * Generates and updates the shortcode based on current input values.
     */
    function updateShortcode() {
        var channelId = $channelIdInput.val().trim();
        var count = parseInt($videoCountInput.val(), 10) || 5;

        // Ensure count is within sane boundaries
        if (count < 1) {
            count = 1;
        }

        // Construct shortcode
        var shortcode = '[micromax_gerdali_story_videos';
        if (channelId) {
            shortcode += ' id="' + channelId + '"';
        } else {
            shortcode += ' id="YOUR_CHANNEL_ID"';
        }
        
        if (count !== 5) {
            shortcode += ' count="' + count + '"';
        }
        shortcode += ']';

        // Update view
        $shortcodeDisplay.text(shortcode);
    }

    // Bind event listeners for input fields
    $channelIdInput.on('input change', updateShortcode);
    $videoCountInput.on('input change', updateShortcode);

    // Initial run
    updateShortcode();

    // Clipboard copy action
    $copyBtn.on('click', function(e) {
        e.preventDefault();
        var textToCopy = $shortcodeDisplay.text();

        if (navigator.clipboard && window.isSecureContext) {
            // Navigator clipboard API
            navigator.clipboard.writeText(textToCopy).then(showCopiedFeedback, fallbackCopy);
        } else {
            // Fallback
            fallbackCopy(textToCopy);
        }
    });

    /**
     * Visual feedback for successful copy
     */
    function showCopiedFeedback() {
        clearTimeout(copyTimeout);
        $copyBtn.addClass('copied');
        copyTimeout = setTimeout(function() {
            $copyBtn.removeClass('copied');
        }, 2000);
    }

    /**
     * Fallback copy method for older browsers or non-secure contexts
     */
    function fallbackCopy(text) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        try {
            document.execCommand('copy');
            showCopiedFeedback();
        } catch (err) {
            console.error('Failed to copy text: ', err);
        }
        $temp.remove();
    }
});
