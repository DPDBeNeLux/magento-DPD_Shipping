function showDPDWindow(url, type, width, height, config) {
   if (type == 'iframe') {
        if (this.window.DPDWindow && this.window.DPDWindow.visible) {
            this.window.DPDWindow.setSize(width, height, true);
            this.window.DPDWindow.setId('DPD_window');
            this.window.DPDWindow.setURL(url);
            this.window.DPDWindow.setDestroyOnClose();
        } else {
            this.window.DPDWindow = new Window({
                className: 'DPD_window',
                width: width,
                height: height,
                minimizable: false,
                maximizable: false,
                destroyOnClose: true,
                id: 'DPD_window',
                showEffectOptions: {
                    duration: 0.4
                },
                hideEffectOptions: {
                    duration: 0.4
                },
                url: url
            });
        }
    } else {
    }
    if (this.window.DPDWindow && !this.window.DPDWindow.visible) {
        this.window.DPDWindow.setZIndex(100);
        this.window.DPDWindow.showCenter(true);

        /*Close on click with overlay*/
        setTimeout(function() {
            Event.observe('overlay_modal', 'click', function(event) {
                closeDPDWindow(event);
            });
        }, 100);
    }
};

function closeDPDWindow(event) {
    Windows.close("DPD_window", event);
};

