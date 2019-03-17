/**
 * Main setupPage application
 */

define([
    'jquery',
    'app/init',
    'app/util',
    'app/map/worker'
], function($, Init, Util, MapWorker){
    'use strict';

    let config = {
        splashOverlayClass: 'pf-splash'                     // class for "splash" overlay
    };

    /**
     * send ajax request for index build
     * @param url
     * @param requestData
     * @param context
     * @param callback
     */
    let sendRequest = (url, requestData, context, callback) => {
        if(requestData.count === 0){
            // first iteration
            context.target.button('loading');
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: requestData,
            context: context
        }).done(function(data){
            callback(this, data);
        }).fail(function(jqXHR, status, error){
            let reason = status + ' ' + error;
            Util.showNotify({title: jqXHR.status + ': Failed. Please retry', text: reason, type: 'warning'});
            this.target.button('reset');
        });
    };

    /**
     * set page observer
     */
    let setPageObserver = () => {
        let body = $('body');

        // navigation (scroll) ----------------------------------------------------------------------------------------
        Util.initPageScroll(body);

        // collapse ---------------------------------------------------------------------------------------------------
        body.find('[data-toggle="collapse"]').css({cursor: 'pointer'}).on('click', function(){
            $(this).find('.pf-animate-rotate').toggleClass('right');
        });

        // buttons ----------------------------------------------------------------------------------------------------
        // exclude "download" && "navigation" buttons
        body.find('.btn')
            .not('.navbar-fixed-bottom .btn')
            .not('[data-action="clearIndex"]')
            .not('[data-action="buildIndex"]')
            .not('[href^="?export"]').on('click', function(e){
            $('.' + config.splashOverlayClass).showSplashOverlay();
        });

        // build/clear index buttons ----------------------------------------------------------------------------------
        // clear index buttons ----------------------------------------------------------------------------------------
        body.find('.btn[data-action="buildIndex"], .btn[data-action="clearIndex"]').on('click', function(e){
            e.preventDefault();
            let element = $(this);
            let url = '/api/setup/' + element.attr('data-action');
            sendRequest(url, {
                type: element.attr('data-type'),
                count: 0,
                offset: 0
            }, {
                target: element,
                url: url
            }, updateIndexCount);
        });

        // tooltips ---------------------------------------------------------------------------------------------------
        body.find('[title]').tooltip();

        // change url (remove logout parameter)
        if(history.pushState){
            history.pushState({}, '', location.protocol + '//' + location.host + location.pathname);
        }
    };

    /**
     * update data count label for "indexed data"
     * @param context
     * @param responseData
     */
    let updateIndexCount = (context, responseData) => {
        let countElement = context.target.closest('.row').children().eq(1).find('kbd');
        countElement.text(responseData.countBuildAll + '/' + responseData.countAll);
        countElement.removeClass('txt-color-success txt-color-danger txt-color-warning');
        if(responseData.countBuildAll >=responseData.countAll){
            countElement.addClass('txt-color-success');
        }else if(responseData.countBuildAll > 0){
            countElement.addClass('txt-color-warning');
        }else{
            countElement.addClass('txt-color-danger');
        }

        context.target.find('.btn-progress').html('&nbsp;&nbsp;' + responseData.progress + '%').css('width', responseData.progress + '%');

        // send next chunk of rows -> import only
        if(
            context.target.attr('data-action') === 'buildIndex' &&
            responseData.countBuildAll < responseData.countAll
        ){
            sendRequest(context.url, {
                type: responseData.type,
                count: responseData.count,
                offset: responseData.offset
            }, {
                target: context.target,
                url: context.url
            }, updateIndexCount);
        }else{
            context.target.button('reset');
        }
    };

    /**
     * perform a basic check if Clients (browser) can connect to the webSocket server
     */
    let testWebSocket = () => {
        let tcpSocketPanel = $('#pf-setup-tcpSocket');
        let webSocketPanel = $('#pf-setup-webSocket');
        let webSocketURI = MapWorker.getWebSocketURL();
        let sslIcon = webSocketURI.startsWith('wss:') ?
            '<i class="fas fa-fw fa-lock txt-color txt-color-success"></i>' :
            '<i class="fas fa-fw fa-unlock txt-color txt-color-warning"></i>';

        webSocketPanel.showLoadingAnimation();

        let removeColorClasses = (el) => {
            el.removeClass (function(index, css){
                return (css.match (/\btxt-color-\S+/g) || []).join(' ');
            });
        };

        /**
         * updates the WebSocket panel with new data
         * @param data
         */
        let updateWebSocketPanel = (data) => {
            let badgeSocketWarning = $('.navbar a[data-anchor="#pf-setup-socket"] .txt-color-warning');
            let badgeSocketDanger = $('.navbar a[data-anchor="#pf-setup-socket"] .txt-color-danger');
            let socketWarningCount = parseInt(badgeSocketWarning.text()) || 0;
            let socketDangerCount = parseInt(badgeSocketDanger.text()) || 0;

            if(data.uri){
                let uriRow = webSocketPanel.find('.panel-body table tr');
                uriRow.find('td:nth-child(2) kbd').html(data.uri.value);
                if(data.uri.status){
                    let statusIcon = uriRow.find('td:nth-child(3) i');
                    removeColorClasses(statusIcon);
                    statusIcon.toggleClass('fa-exclamation-triangle', false).toggleClass('fa-check', true).addClass('txt-color-success');

                    // update head badge. "Decrease" warning count, default for "URI" connection is "warn + 1"
                    socketWarningCount--;
                }
            }

            if(data.status){
                let footer = webSocketPanel.find('.panel-footer h3');
                removeColorClasses(footer);
                footer.text(data.status.label).addClass(data.status.class);

                // update head badge
                switch(data.status.type){
                    case 'success':
                        socketWarningCount = '';
                        socketDangerCount = '';
                        break;
                    case 'warning':
                        break;
                    case 'danger':
                        socketDangerCount = 1;
                        break;
                }
            }

            badgeSocketWarning.text(socketWarningCount ? socketWarningCount : '');
            badgeSocketDanger.text(socketDangerCount ? socketDangerCount : '');
        };

        // update initial
        updateWebSocketPanel({
            uri: {
                value: sslIcon + '&nbsp;' + webSocketURI,
                status: true
            },
            status: {
                type: 'warning',
                label:  'CONNECTING...',
                class: 'txt-color-warning'
            }
        });

        // try to connect to WebSocket server
        let socket = new WebSocket(webSocketURI);

        socket.onopen = (e) => {
            updateWebSocketPanel({
                status: {
                    type: 'warning',
                    label:  'OPEN wait for response...',
                    class: 'txt-color-warning'
                }
            });

            // sent token and check response
            socket.send(JSON.stringify({
                task: 'healthCheck',
                load: tcpSocketPanel.data('token')
            }));

            webSocketPanel.hideLoadingAnimation();
        };

        socket.onmessage = (e) => {
            let response = JSON.parse(e.data);

            if(response === 1){
                // SUCCESS
                updateWebSocketPanel({
                    status: {
                        type: 'success',
                        label:  'CONNECTED',
                        class: 'txt-color-success'
                    }
                });
            }else{
                // Got response but INVALID
                updateWebSocketPanel({
                    status: {
                        type: 'warning',
                        label:  'INVALID RESPONSE',
                        class: 'txt-color-warning'
                    }
                });
            }

        };

        socket.onerror = (e) => {
            updateWebSocketPanel({
                status: {
                    type: 'danger',
                    label:  'CONNECTION ERROR',
                    class: 'txt-color-danger'
                }
            });

            webSocketPanel.hideLoadingAnimation();
        };

        socket.onclose = (closeEvent) => {
            updateWebSocketPanel({
                status: {
                    type: 'danger',
                    label:  'CONNECTION FAILED',
                    class: 'txt-color-danger'
                }
            });

            webSocketPanel.hideLoadingAnimation();
        };
    };

    /**
     * main init "setup" page
     */
    $(function(){

        // show app information in browser console --------------------------------------------------------------------
        Util.showVersionInfo();

        // hide splash loading animation ------------------------------------------------------------------------------
        $('.' + config.splashOverlayClass).hideSplashOverlay();

        setPageObserver();

        testWebSocket();
    });
});