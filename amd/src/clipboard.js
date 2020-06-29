define(['jquery', 'core/str', 'theme_boost/tooltip'], function ($, str, tooltip) {
    return {
        init: function () {
            $('#meet-copy-link-to-clipboard').on('click', function (e) {
                e.preventDefault();
                var $this = $(this);
                copyTextToClipboard($this, $this.data('link'));
            });

            function copyTextToClipboard($element, text) {
                if (!navigator.clipboard) {
                    fallbackCopyTextToClipboard($element, text);
                }
                navigator.clipboard.writeText(text).then(function () {
                    console.log('Async: Copying to clipboard was successful!');
                    showTooltip($element);
                }, function (err) {
                    console.error('Async: Could not copy text: ', err);
                });
            }

            function fallbackCopyTextToClipboard($element, text) {
                var textArea = document.createElement("textarea");
                textArea.value = text;

                // Avoid scrolling to bottom
                textArea.style.top = "0";
                textArea.style.left = "0";
                textArea.style.position = "fixed";

                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    var successful = document.execCommand('copy');
                    var msg = successful ? 'successful' : 'unsuccessful';
                    console.log('Fallback: Copying text command was ' + msg);
                } catch (err) {
                    console.error('Fallback: Oops, unable to copy', err);
                }

                document.body.removeChild(textArea);
                showTooltip($element);
            }

            function showTooltip($element) {
                str.get_string('link_copied', 'meet').then(function (langString) {
                    $element.attr('title', langString);
                    $element.tooltip({trigger: 'manual'});
                    $element.tooltip('show');
                    setTimeout(function () {
                        $element.attr('title', undefined);
                        $element.tooltip('dispose');
                    }, 3000);
                });
            }
        }
    };
});
