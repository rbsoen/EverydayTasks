;"use strict";

/**
 * Force capitalize a string
 * @example "helloWorld".capitalize() == "Helloworld"
 * @return string Capitalized word
 */
String.prototype.capitalize = function(){
    return (
    this.length === 0
        ? ""
        : this[0].toUpperCase() + this.substring(1).toLowerCase()
    );
};

/**
 * Formats the Date object into human-readable form.
 * @example new Date().formatDate() == "Tuesday, 9 November 2021"
 * @return string Formatted date
 */
Date.prototype.formatDate = function() {
    let day_names = [
        "Sunday", "Monday", "Tuesday", "Wednesday",
        "Thursday", "Friday", "Saturday"
    ];

    let month_names = [
        "January", "February", "March", "April",
        "May", "June", "July", "August", "September",
        "October", "November", "December"
    ];

    return `${day_names[this.getDay()]}, ${this.getDate()} ${month_names[this.getMonth()]} ${this.getFullYear()}`;
};

/**
 * Gets the short date format
 * @example new Date().getShortMonth() == "9 Nov"
 * @return string Formatted date
 */
Date.prototype.formatShortDate = function() {
    let month_names = [
        "Jan", "Feb", "Mar", "Apr",
        "May", "Jun", "Jul", "Aug", "Sep",
        "Oct", "Nov", "Dec"
    ];

    return `${this.getDate()} ${month_names[this.getMonth()]}`;
};

(function(d, w, $){
    // set "global" (within this scope) variables
    let timeOuts = [];
    let last_page = "";
    
    // add scripting part
    $('main').after(
        $.create(
            "section", {id: "extra"}
        )
    );
    
    /**
     * Create an activity card with subject, description and actions
     * @param activity Object
     * @return {Node}
     */
    function makeActivityCard(activity){
        // initialize card, first element contains time
        let new_card = $.create(
            "section", {
                className: "card card--start-animation",
                contents: [
                    {tag: "div", className: "card__time", contents: 
                        {tag:"h3", contents: [
                                {tag: "span",
                                    textContent: new Date(activity.date_time)
                                    .formatShortDate()},
                                
                                {tag: "br"},
                                
                                {tag: "span", textContent: new Date(activity.date_time)
                                    .toLocaleTimeString('jpn')
                                    .split(':')
                                    .slice(0,2)
                                    .join(':')}
                                
                            ]
                        }
                    }
                ]
            });

        // create card__description
        var new_card_title = $.create(
            "h4", {
                textContent: activity.subject
            }
        );

        // add category if available
        if (activity.links.category instanceof Object){
            // get category data
            let req = new XMLHttpRequest();
            req.addEventListener("load", function(){
               try {
                   var category = JSON.parse(this.responseText);
               } catch (e) {
                   return;
               }
               // add category upon successfully loading its data
               // hidden text
                new_card_title.appendChild($.create(
                    "span", {
                        className: "hidden",
                        textContent: ", categorized in"
                    }
               ));
                // card badge
               new_card_title.appendChild(
                   $.create(
                       "span", {
                           className: "card__badge",
                           textContent: category.title,
                           style: {"--badge-color": "#" + category.color.toString(16)}
                       }
                   )
               )
            });
            
            // perform request
            req.open('get', activity.links.category.href);
            req.send();
        }

        // make description
        let new_card_description = $.create(
            "div", {
                className:"card__description",
                contents: [
                    new_card_title,
                    {tag:"p", textContent: activity.description}
                ]
            }
        );

        // define actions
        let card_links = {
            edit: `${activity.id}/edit`,
            delete: `${activity.id}/delete`
        };

        // create action list
        let new_card_actions = $.create("ul", {class:"card__actions"});
        for (const key in card_links) {
            let new_action =
                $.create(
                    "li", {
                        contents: {
                            tag: "a",
                            href: card_links[key],
                            textContent: key.capitalize(),
                            className: `button button--${key}`
                        }
                    }
                );
                
            if (key === "edit") {
                new_action.firstElementChild.addEventListener("click", function(e){
                    e.preventDefault();
                    // push page to history
                    loadPage(this.href, true);
                });
            }
            
            new_card_actions.appendChild(new_action);
        }

        // details = description + actions
        let new_card_details = $.create(
            "div", {
                class:"card__details",
                contents: [new_card_description, new_card_actions]
            }
        );

        // card = time + details
        new_card.appendChild(new_card_details);
        return new_card;
    };

    /**
     * Create cards from Activity response, one-by-one.
     * Drawbacks: 
     * @param activities Activity object
     */
    function fadeActivitiesIn(activities) {
        let x = 0;
        for (const activity of activities) {
            x += 1;
            let main_node = $("main");
            timeOuts.push(
                setTimeout(function (a){
                    main_node.appendChild(
                        makeActivityCard(a)
                    );
                }, 100*x, activity)
            );
        }
    }

    /**
     * Handler for the "Show all activities" link
     */
    function addAllActivitiesHandler(all_activities_link) {
        
    }
    
    /**
     * Close any currently-opened form
     */
    function closeForm(){
        let container = $(".fullsize-form-container");
        
        // exit early if no form is found
        if (!container) return;
        
        // remove form smoothly
        container.addEventListener("animationend", function(e){
            container.remove();
        });
        container.classList.add("fullsize-form-container-out");
    };
    
    /**
     * Clear all notifications
     */
    function clearNotifications(){
        var notification;
        while (notification = $('.notification')) {
            notification.remove();
        }
    };
    /**
     * Show a notification
     * @param notification Object with the following keys:
     *  - type: "error", "success" or "normal"
     *  - message: "Notification message"
     */
    function popNotification(notification){
        // delete all existing notifications first
        clearNotifications();
        
        // create a new notification
        let new_notification = 
            $.create(
                "div", {
                    className: `notification notification--${notification.type}`,
                    textContent: notification.message
                }
            );
        
        // close automatically after 3 seconds
        setTimeout(function(){
            new_notification.addEventListener("animationend", function(e){
                new_notification.remove();
            });
            new_notification.classList.add("notification--animation-end");
        }, 3000);
        
        // show the notification
        $('#extra').appendChild(new_notification)
    };
    
    /**
     * Show the edit form for an activity
     * @param activity|null Activity to edit, or for new activities, null.
     */
    function createEditForm(activity){
        // close existing forms
        closeForm();
        
        // if no activity is passed, assume we are making a new one
        if (activity == null) {
            activity = {
                subject: "",
                description: "",
                links: {
                    edit: {
                        method: "post",
                        href: "/api/activity/"
                    }
                }
            }
        }
        
        // make the actual form
        let edit_form = $.create(
            "form", {
                id: "edit-form",
                className: "form-begin-animation",
                contents: [
                    {tag: "fieldset", contents: [
                        {tag:"legend", textContent:"Activity Details"},
                        {tag:"label", for:"subject", textContent:"Subject"},
                        {tag:"input", type:"text", id:"subject", name:"subject", value:activity.subject},
                        {tag:"br"},
                        {tag:"label", for:"description", textContent:"Description"},
                        {tag:"textarea", id:"description", name:"description", rows:3, textContent:activity.description},
                        {tag: "br"},
                        {tag:"label", for:"category", textContent:"Category"},
                        {tag:"select", name:"category", id:"category", contents:[
                            $.create("option", {value:"", textContent:"-- No category --"})
                        ]}
                    ]},
                    {tag: "fieldset", className: "hidden-and-submit", contents: [
                        {
                            tag:"input",
                            id: "submit",
                            type:"submit",
                            className: "button button--edit"
                        },
                        {
                            tag:"button",
                            id: "cancel",
                            textContent: "Cancel",
                            className: "button button--view"
                        }
                    ]}
                ]
            }
        );
        
        // Add container for form overlay
        let edit_form_container = $.create(
            "div", {
                className: "fullsize-form-container",
                contents: [edit_form]
            }
        );
        
        // add categories
        let categories = edit_form.querySelector("#category");
        let category_fetch = new XMLHttpRequest();
        category_fetch.addEventListener("load", function(e){
            try {
                var categories_list = JSON.parse(this.responseText);
            } catch (e) {
                return;
            }
            
            for (let category of categories_list) {
                let cat_option = $.create(
                    "option", {
                        value:category.id,
                        textContent:category.title
                        }
                );
                
                // automatically select the activity's category
                try {
                    if (activity.links.category.id == category.id)
                        cat_option.selected = true
                } catch (e) {}
                
                // add categories to selection
                categories.appendChild(cat_option);
            }
        });
        category_fetch.open("get", "/api/category");
        category_fetch.send();
        
        // Save changes
        let submit_button = edit_form.submit;
        submit_button.addEventListener("click", function(e){
            e.preventDefault();
            popNotification({
                type: "normal",
                message: "Editing activity, please wait."
            });
            
            // custom submit event
            let submit_event = new XMLHttpRequest();
            submit_event.addEventListener("load", function(f){
                // bad request or error
                if (this.status != 200) {
                    popNotification({
                        type: "error",
                        message: "Can't edit activity, try again later."
                    });
                    return;
                }
                popNotification({
                    type: "success",
                    message: "Activity edited successfully!"
                });
                // refresh all activities
                loadPage(last_page, true);
                closeForm();
            });
            
            // extract form data
            edit_form_data = new FormData(edit_form);
            
            // make request
            submit_event.open(
                activity.links.edit.method,
                activity.links.edit.href
            );
            submit_event.setRequestHeader(
                "Content-Type", "application/json"
            );
            submit_event.send(JSON.stringify({
                subject: edit_form_data.get("subject"),
                description: edit_form_data.get("description"),
                category: edit_form_data.get("category")
            }));
            popNotification({
                type: "normal",
                message: "Editing activity, please wait."
            });
        });
        
        // Cancel editing
        let cancel_button = edit_form.querySelector("#cancel");
        cancel_button.addEventListener("click", function(e){
            e.preventDefault();
            history.pushState({}, '', last_page);
            closeForm();
        });
        return edit_form_container;
    }

    /**
     * Loads a page depending on the URL.
     * @param page_name
     * @param push_to_history Bool
     */
    function loadPage(page_name, push_to_history) {
        
        last_page = location.pathname;
        
        if (push_to_history)
            history.pushState({}, '', page_name);
        
        let activities, req;
        let special_case = null;
        
        // special cases: load edit page
        if (special_case = /\/activity\/([0-9a-f]{8})\/edit\/?$/.exec(page_name)) {
            let activity_req = new XMLHttpRequest();
            activity_req.addEventListener("load", function(e){
                // exit on error
                if (this.status != 200) {
                    popNotification({
                        type: "error",
                        message: "Failed to load activity for editing"
                    });
                    return;
                };
                
                let activity;
                
                // verify JSON
                try { activity = JSON.parse(this.responseText) }
                catch (e) {
                    popNotification({
                        type: "error",
                        message: "Failed to load activity for editing"
                    });
                    return;
                }
                
                $('#extra').appendChild(createEditForm(activity));
            });
            activity_req.open('get', `/api/activity/${special_case[1]}`);
            activity_req.send();
            console.log("Edit page invoked");
            return;
        }
        
        // special cases: load delete page
        if (special_case = /\/activity\/([0-9a-f]{8})\/delete\/?$/.exec(page_name)) {
            console.log("Delete page invoked");
            return;
        }
        
        // close all forms
        closeForm();
        
        switch (page_name) {
            case "/activity/add":
                $('#extra').appendChild(createEditForm(null));
                break;
                
            case "/activity/":
                //$(".activity-heading h2").textContent = "Today's Activities";
                req = new XMLHttpRequest();
                req.addEventListener("load", function(e){
                    // load activities
                    try { activities = JSON.parse(this.responseText) } catch (e) { return; }
                    
                    // clear existing timeouts (prevent duplicate card bug)
                    var timeout;
                    while (timeout = timeOuts.pop()) {
                        clearTimeout(timeout);
                    }

                    // delete everything in main
                    while (true) {
                        try { $('main').firstElementChild.remove() }
                        catch (e){ break; }
                    }

                    // load content for the "all activities" page

                    let date = new Date();

                    // header
                    $('main').appendChild(
                        $.create(
                            "header", {
                                className: "activity-heading",
                                contents: [
                                    {tag: "h2", textContent: `Today's Activities (${date.formatDate()})`},
                                    {tag: "a", id: "add", textContent: "Add", href: "add", className: "button button--add"}
                                ]
                            }
                        )
                    );

                    // show all activities link
                    let all_activities_link =
                        $.create("a", {
                            id: "view-all-activities",
                            textContent: "View all activities",
                            href: "all"
                        });
                    $('main').appendChild(all_activities_link);
                    
                    addMainPageHandlers();

                    // activities
                    fadeActivitiesIn(activities);
                })

                // fire the request
                req.open("get", "/api/activity/?for=today");
                req.send();

                break;

            case "/activity/all":
                // ensure loading activities first
                req = new XMLHttpRequest();
                req.addEventListener("load", function(e){
                    // load activities
                    try { activities = JSON.parse(this.responseText) } catch (e) { return; }
                    
                    // clear existing timeouts (prevent duplicate card bug)
                    var timeout;
                    while (timeout = timeOuts.pop()) {
                        clearTimeout(timeout);
                    }
                    
                    // delete everything in main
                    while (true) {
                        try { $('main').firstElementChild.remove() }
                        catch (e){ break; }
                    }

                    // load content for the "all activities" page

                    // header
                    $('main').appendChild(
                        $.create(
                            "header", {
                                className: "activity-heading",
                                contents: [
                                    {tag: "h2", textContent: "All Activities"},
                                    {tag: "a", id: "add", textContent: "Add", href: "add", className: "button button--add"}
                                ]
                            }
                        )
                    );
                    
                    addCommonHandlers();

                    // activities
                    fadeActivitiesIn(activities);
                })

                // fire the request
                req.open("get", "/api/activity/");
                req.send();

                break;
            default:
                break;
        }
    }

    // Page handler
    w.addEventListener("popstate", function(e) {
        console.log("Loading: " + location.pathname);
        loadPage(location.pathname, false);
    })

    /**
     * Add button handlers for the main page
     */
    function addMainPageHandlers(){
        // Main page controls
        addCommonHandlers();
        let view_all_activities = d.getElementById("view-all-activities");
        if (view_all_activities instanceof Node) {
            Object.getPrototypeOf(view_all_activities).click_counter = 0
            view_all_activities.addEventListener("click", function(e){
                e.preventDefault();
                // prevent re-calling when link is clicked multiple times
                if (this.click_counter > 0) return;
                this.click_counter++;
                loadPage("/activity/all", true);
            })
        }
    };
    
    /**
     * Add handlers for card buttons
     */
    function addCommonHandlers(){
        // enhance Add button
        let add_activity_button = d.getElementById("add");
        if (add_activity_button instanceof Node) {
            add_activity_button.addEventListener("click", function(e){
                e.preventDefault();
                loadPage("/activity/add", true);
            })
        }
        
        // enhance actions in cards
        for (
            var
                edit_buttons = $.$("section.card .button--edit"),
                i = 0;
            i < edit_buttons.length;
            i++
        ) {
            let edit_button = edit_buttons[i]
            edit_button.addEventListener("click", function(e){
                e.preventDefault();
                loadPage(edit_button.href, true);
            });
        }
    };
    
    addMainPageHandlers();
    
})(document, window, Bliss);
