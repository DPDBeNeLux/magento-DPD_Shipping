DPD.Shipping.addMethods({
    initialize: function (container, config) {
        if (container == "DPD_window_content") {
            this.iframe = window.parent.document.getElementById('DPD_window_content');
            innerDoc = this.iframe.contentDocument || this.iframe.contentWindow.document;
            this.container = innerDoc.getElementById('parcelshop');
        }
        else {
            this.container = $$(container)[0];
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
    saveParcelShop: function (evt) {
        if (this.container.id == "parcelshop") {
            var shopId = evt.target.id;
            setTimeout(function () {
                parent.Windows.close("DPD_window", evt);
            }, 1);
            this.container = window.parent.document.getElementsByClassName('onestepcheckout-shipping-method-block')[0];
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
                this.container.down('#s_method_dpdparcelshops_dpdparcelshops').click();
                if(price.substring(1) != oldPrice.substring(1)) {
                    priceContainer.addClassName('price-changed');
                    parent.setTimeout(function(){
                        priceContainer.removeClassName('price-changed');
                    }.bind(this), 2000)
                }
            }.bind(this)
        });
    }
})