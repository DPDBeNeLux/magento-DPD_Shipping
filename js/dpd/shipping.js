if (!window.DPD) {
    window.DPD = {};
}
;
DPD.Shipping = Class.create({
    initialize: function (container, config) {
        if (container == "DPD_window_content") {
            this.iframe = window.parent.document.getElementById('DPD_window_content');
            innerDoc = this.iframe.contentDocument || this.iframe.contentWindow.document;
            this.container = innerDoc.getElementById('parcelshop');
        }
        else {
            this.container = $(container);
        }
        this.config = config;
        this.showParcelsLinkClick = this.displayParcelsInline.bind(this);
        this.saveParcelShopClick = this.saveParcelShop.bind(this);
        this.invalidateParcelLinkClick = this.invalidateParcel.bind(this);
        this.saveShipping = this.updateProgressBlock.bind(this);
        this.showExtraInfoHover = this.showExtraInfo.bind(this);
        if (this.container.down('#map_canvas')) {
            this.initGmaps();
        }
        if (this.container.down('.parcelshoplogo')) {
            this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
            this.setParcelshopImage();
        }
        this.bindEvents();
    },
    bindEvents: function () {
        this.showParcelsLink = this.container.down('#showparcels');
        if (this.showParcelsLink) {
            this.showParcelsLink.observe('click', this.showParcelsLinkClick);
        }
        this.shippingRadioButtons = $('checkout-shipping-method-load');
        if (this.shippingRadioButtons) {
            this.shippingRadioButtons.observe('change', this.saveShipping);
        }
        this.showInfo = this.container.down('.extrainfo');
        if (this.showInfo) {
            this.showInfo.observe('click', this.showExtraInfoHover);
        }
        this.parcelShopLink = $$('.parcelshoplink');
        var parcelShopClick = this.saveParcelShopClick;
        if (this.parcelShopLink) {
            this.parcelShopLink.each(function (element) {
                element.observe('click', parcelShopClick);
            });
        }
        this.invalidateParcelsLink = this.container.down('.invalidateParcel');
        if (this.invalidateParcelsLink) {
            this.invalidateParcelsLink.observe('click', this.invalidateParcelLinkClick);
        }
        this.closeGoogleMapsLink = this.container.down('.dpd_close_map');
        if (this.closeGoogleMapsLink) {
            this.closeGoogleMapsLink.observe('click', function (event) {
                event.preventDefault();
                shipping.save();
            });
        }
    },
    displayParcelsInline: function () {
        this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
        var dialog = this.container.down('.dialog');
        if (this.config.gmapsDisplay && !dialog) {
            showDPDWindow(this.config.windowParcelUrl + "?windowed=true",
                'iframe',
                (parseInt(this.config.gmapsWidth.replace("px", "")) + 40), (parseInt(this.config.gmapsHeight.replace("px", "")) + 40),
                this.config
            );
        }
        else {
            this.parcelselectLink = this.container.down('#showparcels');
            if (this.parcelselectLink) {
                var loaderurl = this.config.loaderimage;
                var reloadurl = this.config.ParcelUrl;
                this.parcelselectLink.replace('<div class="dpdloaderwrapper"><span class="dpdloader"></span>' + this.config.loadingmessage + '</div>' +
                    '<input type="hidden" class="DPD-confirmed" value="0"/>');
                new parent.Ajax.Updater({success: 'dpd'}, reloadurl, {
                    type: "GET",
                    asynchronous: true,
                    evalScripts: true,
                    onComplete: function () {
                        this.initGmaps();
                    }
                })
            }
        }
    }, initGmaps: function () {
        this.mapcanvas = this.container.down('#map_canvas');
        this.shops = this.container.down('.shops');
        this.wrapper = this.container.down('#parcelshop');
        var padding = 0;
        if (this.container.id == "parcelshop") {
            this.wrapper = this.container;
        }
        this.wrapper.style.height = this.config.gmapsHeight;
        this.wrapper.style.width = this.config.gmapsWidth;
        this.map_options = {
            mapTypeId: google.maps.MapTypeId.ROAsetParcelshopImageDMAP
        };
        var map = new google.maps.Map(this.mapcanvas, this.map_options);
        var geocoder = new google.maps.Geocoder();
        var configForMarkers = this.config;
        var marker_image = new google.maps.MarkerImage(configForMarkers.gmapsIcon, new google.maps.Size(57, 62), new google.maps.Point(0, 0), new google.maps.Point(0, 31));
        var shadow = new google.maps.MarkerImage(configForMarkers.gmapsIconShadow, new google.maps.Size(85, 55), new google.maps.Point(0, 0), new google.maps.Point(0, 55));
        var customImage = configForMarkers.gmapsCustomIcon;
        var infowindow = new google.maps.InfoWindow();
        window.markers = new Array();
        var markerBounds = new google.maps.LatLngBounds();
        $H(configForMarkers).each(function (pair) {
            if (pair.key.indexOf("shop") != -1) {
                var content = pair.value.gmapsMarkerContent;
                if (pair.value.special && customImage != "") {
                    var marker = new google.maps.Marker({
                        map: map,
                        position: new google.maps.LatLng(pair.value.gmapsCenterlat, pair.value.gmapsCenterlng),
                        icon: customImage
                    });
                }
                else {
                    var marker = new google.maps.Marker({
                        map: map,
                        position: new google.maps.LatLng(pair.value.gmapsCenterlat, pair.value.gmapsCenterlng),
                        icon: marker_image,
                        shadow: shadow
                    });
                    if (!pair.value.special)
                        markerBounds.extend(new google.maps.LatLng(pair.value.gmapsCenterlat, pair.value.gmapsCenterlng));
                }
                window.markers.push(marker);
                google.maps.event.addListener(marker, 'click', (function (marker) {
                    return function () {
                        infowindow.setContent(content);
                        infowindow.open(map, marker);
                    }
                })(marker));
            }
            map.fitBounds(markerBounds);
        });
        if (this.shops) {
            this.shops.scrollTop = 1;
            this.checkInfoClick();
        }
    }, saveParcelShop: function (evt) {
         if (this.container.id == "parcelshop") {
            var shopId = evt.target.id;
            setTimeout(function () {
                parent.Windows.close("DPD_window", evt);
            }, 1);
            this.container = window.parent.document.getElementById('checkout-shipping-method-load');
        }
        else {
            var shopId = evt.target.id;
        }
        if (!shopId) {
            shopId = evt.target.parentNode.id;
        }
        this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
        var reloadurl = this.config.saveParcelUrl;
        var data = this.config[shopId];
        var loaderurl = this.config.loaderimage;
        var parcelshop = this.container.down('#parcelshop');

        parcelshop.update('<div class="dpdloaderwrapper" style="margin-bottom:35px;"><span class="dpdloader"></span><span class="message"></span></div><input type="hidden" class="DPD-confirmed" value="0"/>');
        new parent.Ajax.Request(reloadurl, {
            method: "POST",
            asynchronous: false,
            evalScripts: true,
            parameters: data,
            onSuccess: function(data) {
                this.container.down('#dpd').update(data.responseText);

                var price = this.container.down('#custom-shipping-amount').value;
                var priceContainer = this.container.down('label[for="s_method_dpdparcelshops_dpdparcelshops"] span');
                var oldPrice = priceContainer.innerHTML;
                priceContainer.update(price);
                if(price.substring(1) != oldPrice.substring(1)) {
                    priceContainer.addClassName('price-changed');
                    parent.setTimeout(function(){
                        priceContainer.removeClassName('price-changed');
                    }.bind(this), 2000)
                }
            }.bind(this)
        });

    }, invalidateParcel: function (evt) {
        var reloadurl = this.config.invalidateParcelUrl;
        var loaderurl = this.config.loaderimage;
        var parcelshop = this.container.down('#parcelshop');
        var dialog = this.container.down('.dialog');
        this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
        if (this.config.gmapsDisplay && !dialog) {
            this.container.down('#s_method_dpdparcelshops_dpdparcelshops').checked = true;
            showDPDWindow(this.config.windowParcelUrl + "?windowed=true",
                'iframe',
                (parseInt(this.config.gmapsWidth.replace("px", "")) + 40), (parseInt(this.config.gmapsHeight.replace("px", "")) + 40),
                this.config
            );
        }
        else {
            parcelshop.update('<div class="dpdloaderwrapper"><span class="dpdloader"></span>' + this.config.loadingmessage + '</div>' +
                '<input type="hidden" class="DPD-confirmed" value="0"/>');
            new parent.Ajax.Updater({success: this.container.down('#dpd')}, reloadurl, {
                type: "GET",
                asynchronous: true,
                evalScripts: true
            })
        }
    }, showExtraInfo: function (evt) {
        if (this.container.down('.extrainfowrapper').visible()) {
            this.container.down('.extrainfowrapper').hide();
        }
        else {
            var left = evt.target.offsetLeft;
            var openinghours = this.container.down('.extrainfowrapper');
            this.container.down('.extrainfowrapper').show().setStyle({left: left + 'px'});
        }
    }, checkInfoClick: function () {
        this.showInfoBubble = $$('.show-info-bubble');
        if (this.showInfoBubble) {
            this.showInfoBubble.each(function (bubble) {
                bubble.observe('click', function () {
                    marker = window.markers[this.id];
                    google.maps.event.trigger(marker, "click");
                });
            })
        }

    }, setParcelshopImage: function () {
        var shopId = "shop" + this.container.down('.parcelshopId').value;
        if (this.config[shopId]) {
            if (this.config[shopId]['special'] && this.config[shopId]['specialImage'] != "") {
                this.container.down('.parcelshoplogo').src = this.config[shopId]['specialImage'];
            }
        }
    }, updateProgressBlock: function () {
        var progressContents = $$('#checkout-progress-wrapper a[href="#shipping_method"]')[0];
        if (!progressContents) {
            progressContents = $$('.opc-block-progress a[href="#shipping_method"]')[0];
        }
        if (progressContents != undefined) {
            if (!$('s_method_dpdparcelshops_dpdparcelshops').checked && progressContents.up().next().innerHTML) {
                var request = new Ajax.Request(
                    shipping.saveUrl,
                    {
                        method: 'post',
                        onSuccess: checkout.reloadProgressBlock(),
                        parameters: Form.serialize(shipping.form)
                    }
                );
            }
        }
    }
});