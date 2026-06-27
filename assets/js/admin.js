/**
 * Admin JavaScript for Micromax Gerdali Video Story Shortcode Generator
 * Version: 1.7.0 (Pro Dashboard Interactions)
 */

jQuery(document).ready(function($) {
    // DOM Elements
    var $channelIdInput = $('#micromax-gerdali-channel-id');
    var $videoCountInput = $('#micromax-gerdali-video-count');
    var $videoCountRange = $('#micromax-gerdali-video-count-range');
    var $shortcodeDisplay = $('#micromax-gerdali-shortcode-display');
    var $copyBtn = $('#micromax-gerdali-copy-btn');
    var $livePreview = $('#micromax-gerdali-live-preview');
    var copyTimeout;

    // Synchronize Range and Number inputs
    $videoCountRange.on('input', function() {
        $videoCountInput.val($(this).val());
        updateShortcode();
    });

    $videoCountInput.on('input change', function() {
        var val = parseInt($(this).val(), 10);
        if (isNaN(val)) val = 5;
        if (val < 1) val = 1;
        if (val > 50) val = 50;
        
        $(this).val(val);
        $videoCountRange.val(val);
        updateShortcode();
    });

    /**
     * Build the Live UI Preview elements dynamically
     */
    function updateLivePreview(count) {
        $livePreview.empty();
        
        var titles = [
            'How to Build a Custom Website Layout',
            '10 Tips for Professional Video Editing',
            'Exploring the Secrets of the Ocean',
            'Modern Design Trends You Must Know',
            'A Complete Guide to Web Development'
        ];

        for (var i = 0; i < count; i++) {
            var titleText = titles[i % titles.length] + ' (Vol. ' + (Math.floor(i / titles.length) + 1) + ')';
            var index = (i % 5) + 1;
            // High quality placeholder images from Unsplash matching theme
            var imgUrl = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100&auto=format&fit=crop&q=60';
            if (index === 2) imgUrl = 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=100&auto=format&fit=crop&q=60';
            if (index === 3) imgUrl = 'https://images.unsplash.com/photo-1550684848-fac1c5b4e853?w=100&auto=format&fit=crop&q=60';
            if (index === 4) imgUrl = 'https://images.unsplash.com/photo-1563089145-599997674d42?w=100&auto=format&fit=crop&q=60';
            if (index === 5) imgUrl = 'https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=100&auto=format&fit=crop&q=60';

            var itemHTML = 
                '<div class="preview-story-item">' +
                    '<div class="preview-story-circle">' +
                        '<div class="inner-circle">' +
                            '<img src="' + imgUrl + '" alt="preview thumbnail" />' +
                        '</div>' +
                        '<div class="preview-play-btn"></div>' +
                    '</div>' +
                    '<span class="preview-story-title">' + titleText + '</span>' +
                '</div>';
            
            $livePreview.append(itemHTML);
        }
    }

    /**
     * Generates and updates the shortcode and live preview components.
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

        // Update live layout preview widget
        updateLivePreview(count);
    }

    // Bind event listeners for input fields
    $channelIdInput.on('input change', updateShortcode);

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

    // Tab Switching Logic
    $('.micromax-gerdali-nav-item').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).data('tab');
        
        // Remove active class from buttons & panels
        $('.micromax-gerdali-nav-item').removeClass('active');
        $('.micromax-gerdali-tab-panel').removeClass('active');
        
        // Add active class to clicked button and target panel
        $(this).addClass('active');
        $('#tab-' + targetTab).addClass('active');
    });

    // FAQ Accordion Logic
    $('.faq-trigger').on('click', function(e) {
        e.preventDefault();
        var $parent = $(this).closest('.faq-item');
        var isOpen = $parent.hasClass('active');
        
        // Close all items
        $('.faq-item').removeClass('active');
        
        // Toggle if it wasn't already open
        if (!isOpen) {
            $parent.addClass('active');
        }
    });
});
