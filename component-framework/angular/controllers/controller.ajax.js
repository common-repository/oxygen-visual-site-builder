/**
 * All AJAX requests
 * 
 */

CTFrontendBuilder.controller("ControllerAJAX", function($scope, $http, $timeout) {

    // cache for loaded posts data
    $scope.postsData = [];
    
    /**
     * Send Components Tree and page settings to WordPress 
     * in JSON format to save as post content and meta
     * 
     * @since 0.1
     */

    $scope.savePage = function(autoSave) {

        if (!autoSave) {
            $scope.showLoadingOverlay("savePage()");
        }

        var params = {
            // CSS classes
            classes : $scope.classes,
            
            // Custom Selectors
            custom_selectors : $scope.customSelectors,
            style_sets : $scope.styleSets,

            // Style Sheets
            style_sheets : $scope.styleSheets,            

            // Settings
            page_settings : $scope.pageSettings,
            global_settings : $scope.globalSettings,
        };

        // store the activeSelectors state to each of the components in the tree
        angular.forEach($scope.activeSelectors, function(selector, id) {
            $scope.findComponentItem($scope.componentsTree.children, id, $scope.updateComponentActiveSelector, selector);
        });

        var data =  { 
            params: params,
            tree: $scope.componentsTree
        }

        // Convert Components Tree to JSON string
        var data = JSON.stringify(data);

        var params = {
            action : 'ct_save_components_tree',
            post_id : CtBuilderAjax.postId,
            nonce : CtBuilderAjax.nonce,
        };

        if(jQuery('body').hasClass('ct_inner')) {
            params['ct_inner'] = true;
        }

        // Send AJAX request
        $http({
            url : CtBuilderAjax.ajaxUrl,
            method : "POST",
            params : params,
            data : data,
            transformResponse: false,
        })
        .success(function(data, status, headers, config) {
            try {
                if (!autoSave) {
                    var response = JSON.parse(data);
                    //console.log(response);
                    if ( response === 0 ) {
                        alert('YOUR PAGE WAS NOT SAVED BECAUSE YOU ARE NOT LOGGED IN. Open a new browser tab and log back in to WordPress. Then attempt to save the page again.');
                    }
                    else
                    if ( response['post_saved'] == 0 ) {
                        console.log(data);
                        alert('Error occured while saving');
                    }
                    else {
                        $scope.allSaved();
                        // update page CSS cache
                        // $scope.updatePageCSS();
                    }
                    $scope.hideLoadingOverlay("savePage()");
                }
                else {
                    var response = JSON.parse(data);
                    if ( response['post_saved'] != 0 ) {
                        $scope.allSaved();
                    }
                }
            } 
            catch (err) {
                console.log(data);
                console.log(err);
                if (!autoSave) {
                    alert('Error occured while saving');
                }
            }
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
            
            alert('Error occured while saving');
            if ( !autoSave ) {
                $scope.hideLoadingOverlay("savePage()");
            }
        });
    }


    /**
     * Update CSS cache
     * 
     * @since 1.1.1
     * @author Ilya K.
     */

    $scope.updatePageCSS = function() {

        $scope.showLoadingOverlay("updatePageCSS()");

        // Send AJAX request
        $http({
            url : CtBuilderAjax.permalink,
            method : "POST",
            params : {
                xlink : 'css',
                action : 'save-css',
            },
            transformResponse: false,
        })
        .success(function(data, status, headers, config) {
            //console.log(data, status);
            $scope.hideLoadingOverlay("updatePageCSS()");
        })
        .error(function(data, status, headers, config) {
            alert('Error occured while saving CSS');
            console.log(data, status);
            $scope.hideLoadingOverlay("updatePageCSS()");
        });
    }


    /**
     * updates the active Selector into the provided item out of the component tree
     * 
     * @since 0.3.3
     * @author gagan goraya
     */   

    $scope.updateComponentActiveSelector = function(id, item, selector) {

        /**
         * Check if no item found becuase it may be a custom selector
         */

        if (!item) {
            return;
        }

        item.options['activeselector'] = selector;
    }


    /**
     * Send single component or Array of same level components 
     * to save as "ct_template" post via AJAX call
     * 
     * @since 0.2.3
     * @author Ilya K.
     */

    $scope.saveComponentAsView = function(key, component) {

        var params = {
                action : 'ct_save_component_as_view',
                name : $scope.componentizeOptions.name,
                post_id : CtBuilderAjax.postId,
                nonce : CtBuilderAjax.nonce,
            };

        // component(s) to save
        if ( component.constructor === Array ) {
            var children = component;
        }
        else {
            var children = [component];
        }

        // Send AJAX request
        $http({
            url: CtBuilderAjax.ajaxUrl,
            method: "POST",
            params: params,
            data: {
                    'id' : 0,
                    'name' : 'root',
                    'depth' : 0,
                    'children': children
                }
        })
        .success(function(data, status, headers, config) {
            //console.log(data);
            $scope.hideLoadingOverlay("saveComponentAsView()");

            if ( data != 0 ) {
                alert("Re-usable part \"" + $scope.componentizeOptions.name + "\" saved successfully.");
                $scope.replaceReusablePart(key, data);
            } 
            else {
                alert("Error occured while saving \"Re-usable part\".");
            }
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
            alert ("Error occured while saving \"Re-usable part\".");
            $scope.hideLoadingOverlay("saveComponentAsView()");
        });
    }

    /**
     * Get Components Tree JSON via AJAX
     * 
     * @since 0.1.7
     * @author Ilya K.
     */

    $scope.loadComponentsTree = function(callback, postId, hasSection, componentId) {

        if ($scope.log) {
            console.log("loadComponentsTree()", postId, hasSection, componentId);
        }

        $scope.showLoadingOverlay("loadComponentsTree()");

        // set default post id
        if ( postId === undefined ) {
            postId = CtBuilderAjax.postId;
        }

        var params = {
                action : 'ct_get_components_tree',
                id : postId,
                post_id : CtBuilderAjax.postId,
                nonce : CtBuilderAjax.nonce,
            };

        
        if(jQuery('body').hasClass('ct_inner')) {
            params['ct_inner'] = true;
        }
        

        // Send AJAX request
        $http({
            url : CtBuilderAjax.ajaxUrl,
            method : "POST",
            params : params,
            transformResponse: false,
        })
        .success(function(data, status, headers, config) {
            try {
                var response = JSON.parse(data);
                callback(response, postId, hasSection, componentId);
            } 
            catch (err) {
                console.log(data, err);
                alert('Error occured while loading post: '+postId);
            }
            $scope.hideLoadingOverlay("loadComponentsTree()");
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
            alert('Error occured while loading post: '+postId);
        });
    }


    /**
     * Get WordPress shortcodes generated HTML
     * 
     * @since 0.2.3
     */

    $scope.renderShortcode = function(id, shortcode) {

        var url = CtBuilderAjax.permalink,
            data = {};

        // if archive
        if (CtBuilderAjax.ctTemplateArchive) {
            data.term = $scope.template.postData.term;
            
            // if the postData is empty
            if(!$scope.template.postData || !$scope.template.postData.permalink) {
                url = CtBuilderAjax.ajaxUrl;
            }
            else {
                url = $scope.template.postData.permalink;
            }
        }

        // if single
        if (CtBuilderAjax.ctTemplateSingle) {
            data.post = $scope.template.postData;

            // if the postData is empty
            if(!$scope.template.postData || !$scope.template.postData.permalink){
                url = CtBuilderAjax.ajaxUrl;
            }
            else {
                // lets make an ajax call directly to the frontend single
                url = data.post.permalink;
            }
        }

        var params = {
                action : 'ct_render_shortcode',
                shortcode_name : shortcode,
                post_id : CtBuilderAjax.postId,
                nonce : CtBuilderAjax.nonce,
            };

        // Send AJAX request
        $http({
            //url : CtBuilderAjax.ajaxUrl,
            url: url,
            method : "POST",
            params : params,
            data : JSON.stringify($scope.component.options[id]),
            transformResponse : false,
        })
        .success(function(data, status, headers, config) {
            if (data || data === "") { // shortcode can return blank and it is ok
                var component = $scope.getComponentById(id);
                component.html(data);
            }
            else {
                console.log(data, status);
                alert('Error occured while rendering shortcode');
            }
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
            alert('Error occured while rendering shortcode');
        });
    }


    /**
     * Remove warning msg for non-chrome browsers
     * 
     * @since 0.3.4
     * @author gagan goraya
     */

    $scope.removeChromeModal = function(e) {
        
        e.stopPropagation();
        e.preventDefault();
     
        
        if(!jQuery(e.target).hasClass('ct-chrome-modal-bg') && !jQuery(e.target).hasClass('ct-chrome-modal-hide'))
            return;
        
        var params = {
                action : 'ct_remove_chrome_modal',
                post_id : CtBuilderAjax.postId,
                nonce : CtBuilderAjax.nonce,
            };

        // Send AJAX request
        $http({
            url : CtBuilderAjax.ajaxUrl,
            method : "POST",
            params : params,
            transformResponse : false,
        })
        .success(function(data, status, headers, config) {
            jQuery('.ct-chrome-modal-bg').remove();
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
            alert('Error occured while dismissing the notice');
        });
    }


    /**
     * Get SVG Icon sets
     * 
     * @since 0.2.1
     */

    $scope.loadSVGIconSets = function() {

        var params = {
                action: 'ct_get_svg_icon_sets',
                post_id : CtBuilderAjax.postId,
                nonce : CtBuilderAjax.nonce,
            };

        // Send AJAX request
        $http({
            url : CtBuilderAjax.ajaxUrl,
            method : "POST",
            params : params,
            transformResponse : false,
        })
        .success(function(data, status, headers, config) {
            //console.log(data);
            try {
                var sets = JSON.parse(data);

                // update scope
                $scope.SVGSets = sets;   
                // set first set as current
                $scope.currentSVGSet = Object.keys(sets)[0]; 
            } 
            catch (err) {
                console.log(data);console.log(err);
                alert('Error occured while loading SVG icons');
            }
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
            alert('Error occured while loading SVG icons');
        });
    }


    /**
     * Load WP Post object (or array of post objects from one term) 
     * 
     * @since 0.2.0
     */

    $scope.loadTemplateData = function(callback, previewPostId) {
        
        $scope.showLoadingOverlay("loadTemplateData()");
        
        var params = {
                action : 'ct_get_template_data',
                template_id : CtBuilderAjax.postId,
                preview_post_id : previewPostId,
                post_id : CtBuilderAjax.postId,
                nonce : CtBuilderAjax.nonce,
            };

        // Send AJAX request
        $http({
            url : CtBuilderAjax.ajaxUrl,
            method : "POST",
            params : params,
            transformResponse : false,
        })
        .success(function(data, status, headers, config) {
            //console.log(data);
            try {
                var response = JSON.parse(data);
                //console.log(response);
                callback(response);
            } 
            catch (err) {
                console.log(data);
                console.log(err);
                alert('Failed to load template data');
            }
            $scope.hideLoadingOverlay("loadTemplateData()");
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
            alert('Failed to load template data');
            $scope.hideLoadingOverlay("loadTemplateData()");
        });
    }


    /**
     * Load WP Post object
     * 
     * @since 0.2.3
     * @author Ilya K.
     */

    $scope.loadPostData = function(callback, postId, componentId) {

        // if data exists in the cache
        if($scope.postsData[postId]) {
            callback($scope.postsData[postId], componentId);
            return;
        }

        $scope.showLoadingOverlay("loadPostData()");
        
        var params = {
                action : 'ct_get_post_data',
                id : postId,
                post_id : CtBuilderAjax.postId,
                nonce : CtBuilderAjax.nonce,
            };

        // Send AJAX request
        $http({
            url : CtBuilderAjax.ajaxUrl,
            method : "POST",
            params : params,
            transformResponse : false,
        })
        .success(function(data, status, headers, config) {
            //console.log(data);
            try {
                var response = JSON.parse(data);
                callback(response, componentId);
                // save in cache
                $scope.postsData[postId] = response;
            } 
            catch (err) {
                console.log(data);console.log(err);
                alert('Failed to load post data. ID: '+postId);
            }
            $scope.hideLoadingOverlay("loadPostData()");
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
            alert('Failed to load post data. ID: '+postId);
            $scope.hideLoadingOverlay("loadPostData()");
        });
    }


    /**
     * Send PHP/HTML code block to server to execute
     * 
     * @since 0.3.1
     */

    $scope.execCode = function(code, placholderSelector, callback) {
        
        var url = CtBuilderAjax.permalink,
            data = {
                code: $scope.b64EncodeUnicode(code),
                query: CtBuilderAjax.query
            };

        // if archive
        if (CtBuilderAjax.ctTemplateArchive) {
            data.term = $scope.template.postData.term;
            
            // if the postData is empty
            if(!$scope.template.postData || !$scope.template.postData.permalink) {
                url = CtBuilderAjax.ajaxUrl;
            }
            else {
                url = $scope.template.postData.permalink;
            }
        }

        // if single
        if (CtBuilderAjax.ctTemplateSingle) {
            data.post = $scope.template.postData;

            // if the postData is empty
            if(!$scope.template.postData || !$scope.template.postData.permalink){
                url = CtBuilderAjax.ajaxUrl;
            }
            else {
                // lets make an ajax call directly to the frontend single
                url = data.post.permalink;
            }
        }

        // Convert Components Tree to JSON
        // escape special characters
        /*data.code = data.code.replace(/\n/g, "\\n")
                                      .replace(/\r/g, "\\r")
                                      .replace(/\t/g, "\\t");*/
        data = JSON.stringify(data);

        // Send AJAX request
        $http({
            method: "POST",
            transformResponse: false,
            url: url,
            params: {
                action: 'ct_exec_code',
                post_id: CtBuilderAjax.postId,
                nonce: CtBuilderAjax.nonce,
            },
            data: data,
        })
        .success(function(data, status, headers, config) {
            
            // this one ensures that blank means blank, not spaces
            if(data.trim().length === 0)
                data='';

            // if data is html document. use jquery to extract the content only
            if(data.indexOf('<html') > -1) {
                data = jQuery('<div>').append(data).find('.ct-code-block').html();
            }

            // get rid of any javascript rendered here.
            data = jQuery('<div>').append(data);
            data.find('script').remove();
            data = data.html();

            callback(data, placholderSelector);
        })
        .error(function(data, status, headers, config) {
            console.log(data, status);
        });
    }

});