;"use strict";

/**
 * Force capitalize a string
 * @example "helloWorld".capitalize() == "Helloworld"
 * @return string Capitalized word
 */
String.prototype.capitalize = function() {
    return (
        this.length === 0 ?
        "" :
        this[0].toUpperCase() + this.substring(1).toLowerCase()
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

/**
 * Gets a standard time string
 * @example new Date().getTimeString() == "15:28"
 * @return string Formatted time
 */
Date.prototype.getTimeString = function() {
    var hours = this.getHours();
    var minutes = this.getMinutes();

    // add a zero to the hour as needed
    hours =
        hours > 9
        ? hours
        : "0" + hours;

    // add a zero to the minute as needed
    minutes =
        minutes > 9
        ? minutes
        : "0" + minutes;

    return `${hours}:${minutes}`
};

(function(d, w, $) {
    /* ----- begin utility vars and functions ----- */

    // set "global" (within this scope) variables
    let timeOuts = [];
    let ajax_requests = [];
    let last_page = "";

    // add scripting part
    $('main').after(
        $.create(
            "section", {
                id: "extra"
            }
        )
    );

    /**
     * Wrapper around $.fetch, in order to allow it to be aborted
     * from abortAllRequests()
     * @param {string} target       The URL to fetch
     * @param {object} options      Fetch options
     * @param {function} then_func  Function to be run if succeeded
     * @param {function} catch_func Function to be run if failed
     */
    function addRequest(target, options, then_func, catch_func){
        var ajax = $.fetch(target, options);
        ajax_requests.push(ajax);
        ajax.then(then_func).catch(catch_func);
    }

    /**
     * Abort all pending AJAX requests
     */
    function abortAllRequests(){
        for (ajax of ajax_requests) {
            ajax.xhr.abort()
        }
    }

    /**
     * Abort all pending timed actions
     */
    function abortAllTimeouts(){
        var timeout;
        while (timeout = timeOuts.pop()) {
            clearTimeout(timeout);
        }
    }

    /* ----- site-specific vars and functions ----- */

    /**
     * Create an activity card with subject, description and actions
     * @param activity Object
     * @return {Node}
     */
    function makeActivityCard(activity) {
        // initialize card, first element contains time
        let new_card = $.create(
            "section", {
                className: "card",
                contents: [{
                    tag: "div",
                    className: "card__time",
                    contents: {
                        tag: "h3",
                        contents: [
                            {tag: "span",
                                textContent: new Date(activity.date_time)
                                    .formatShortDate()
                            },
                            {tag: "br"},
                            {tag: "span",
                                textContent: new Date(activity.date_time)
                                    .getTimeString()
                            }
                        ]
                    }
                }]
            });

        // create card__description
        var new_card_title = $.create(
            "h4",
            {textContent: activity.subject}
        );

        // add category if available
        if (activity.links.category instanceof Object) {
            // get category data
            addRequest(
                activity.links.category.href,
                {
                    method: "get",
                    responseType: "json"
                },
                function(cat_req) {
                    // add category upon successfully loading its data
                    category = cat_req.response;

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
                                style: {
                                    "--badge-color": "#" + category.color.toString(16)
                                }
                            }
                        )
                    )
                },
                function(e){}
            );
        }

        // make description
        let new_card_description = $.create(
            "div", {
                className: "card__description",
                contents: [
                    new_card_title,
                    {tag: "p", textContent: activity.description}
                ]
            }
        );

        // define actions
        let card_links = {
            edit: `/activity/${activity.id}/edit`,
            delete: `/activity/${activity.id}/delete`
        };

        // create action list
        let new_card_actions = $.create("ul", {
            class: "card__actions"
        });

        // action buttons
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

            // associate links with their actions
            new_action.firstElementChild.addEventListener("click", function(e) {
                e.preventDefault();
                doRoute(this.href, true);
            });

            // add buttons
            new_card_actions.appendChild(new_action);
        }

        // details = description + actions
        let new_card_details = $.create(
            "div", {
                class: "card__details",
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
     * @param activities An array of activity objects
     */
     function fadeActivitiesIn(activities) {
        let activity_delay = 0;
        for (const activity of activities) {
            activity_delay += 1;
            let main_node = $("main");

            timeOuts.push(
                setTimeout(function(activity) {
                    main_node.appendChild(
                        makeActivityCard(activity)
                    );
                }, 100 * activity_delay, activity)
            );
        }
    }

    /**
     * Close any currently-opened form
     */
    function closeForm() {
        let container = $(".fullsize-form-container");

        // exit early if no form is found
        if (!container) return;

        // remove form smoothly
        container.addEventListener("animationend", function(e) {
            container.remove();
        });
        container.classList.add("fullsize-form-container-out");
    };

    /**
     * Delete everything in the page
     */
    function clearWholePage() {
        while (true) {
            try { $("main").firstElementChild.remove() }
            catch (e) { break; }
        }
    }

    /**
     * Add a loading screen to the page
     */
    function addLoadingScreen() {
        removeLoadingScreen();
        $("main").appendChild(
            $.create("div", {
                className: "loading"
            })
        )
    }

    /**
     * Remove the loading screen
     */
    function removeLoadingScreen() {
        try { $(".loading").remove(); }
        catch (e) { }
    }

    /**
     * Clear all notifications
     */
    function clearNotifications() {
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
     * @example popNotification({type: "success", message: "Action completed successfully"})
     */
    function pushNotification(notification) {
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
        setTimeout(function() {
            new_notification.addEventListener("animationend", function(e) {
                new_notification.remove();
            });
            new_notification.classList.add("notification--animation-end");
        }, 3000);

        // show the notification
        $('#extra').appendChild(new_notification)
    };

    /**
     * Show the edit or new form for an activity
     * @param activity|null Activity to edit, or for new activities, null.
     */
    function createEditForm(activity) {
        // close existing forms
        closeForm();

        let is_new_activity = false;

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
            is_new_activity = true;
        }

        // make the actual form
        let edit_form = $.create(
            "form", {
                id: "edit-form",
                className: "form-begin-animation",
                contents: [{
                        tag: "fieldset",
                        contents: [
                            {
                                tag: "legend",
                                textContent: "Activity Details"
                            },
                            {
                                tag: "label",
                                for: "subject",
                                textContent: "Subject"
                            },
                            {
                                tag: "input",
                                type: "text",
                                id: "subject",
                                name: "subject",
                                value: activity.subject
                            },
                            {
                                tag: "br"
                            },
                            {
                                tag: "label",
                                for: "description",
                                textContent: "Description"
                            },
                            {
                                tag: "textarea",
                                id: "description",
                                name: "description",
                                rows: 3,
                                textContent: activity.description
                            },
                            {
                                tag: "br"
                            },
                            {
                                tag: "label",
                                for: "category",
                                textContent: "Category"
                            },
                            {
                                tag: "select",
                                name: "category",
                                id: "category",
                                contents: [
                                    $.create("option", {
                                        value: "",
                                        textContent: "-- No category --"
                                    })
                                ]
                            }
                        ]
                    },
                    {
                        tag: "fieldset",
                        className: "hidden-and-submit",
                        contents: [{
                                tag: "input",
                                id: "submit",
                                type: "submit",
                                className: "button button--edit"
                            },
                            {
                                tag: "button",
                                id: "cancel",
                                textContent: "Cancel",
                                className: "button button--view"
                            }
                        ]
                    }
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

        // add categories to the selection
        let categories = edit_form.querySelector("#category");
        addRequest(
            "/api/category",
            {
                method: "get",
                responseType: "json"
            },
            function(cat_req) {
                categories_list = cat_req.response;
                for (let category of categories_list) {
                    let cat_option = $.create(
                        "option", {
                            value: category.id,
                            textContent: category.title
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
            },
            function(e){}
        );

        // Save changes
        let submit_button = edit_form.submit;
        submit_button.addEventListener("click", function(e) {
            e.preventDefault();

            if (is_new_activity) {
                pushNotification({
                    type: "normal",
                    message: "Creating activity, please wait."
                });
            } else {
                pushNotification({
                    type: "normal",
                    message: "Editing activity, please wait."
                });
            }

            // extract form data
            edit_form_data = new FormData(edit_form);

            // custom submit event
            addRequest(
                activity.links.edit.href,
                {
                    method: activity.links.edit.method,
                    responseType: "json",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    data: JSON.stringify({
                        subject: edit_form_data.get("subject"),
                        description: edit_form_data.get("description"),
                        category: edit_form_data.get("category")
                    })
                },
                function(e) {
                    if (is_new_activity) {
                        pushNotification({
                            type: "success",
                            message: "Activity created successfully!"
                        });
                    } else {
                        pushNotification({
                            type: "success",
                            message: "Activity edited successfully!"
                        });
                    }
                    // refresh all activities
                    doRoute(last_page, true);
                    closeForm();
                },
                function(e) {
                    if (is_new_activity) {
                        pushNotification({
                            type: "error",
                            message: "Can't create activity, try again later."
                        });
                    } else {
                        pushNotification({
                            type: "error",
                            message: "Can't edit activity, try again later."
                        });
                    }
                }
            );
        });

        // Cancel editing
        let cancel_button = edit_form.querySelector("#cancel");
        cancel_button.addEventListener("click", function(e) {
            e.preventDefault();
            history.pushState({}, '', last_page);
            closeForm();
        });
        return edit_form_container;
    }

    /**
     * Show the delete form for an activity
     * @param activity Activity
     */
    function createDeleteForm(activity) {
        // close existing forms
        closeForm();

        // make the actual form
        let delete_form = $.create(
            "form", {
                id: "delete-form",
                className: "form-begin-animation",
                contents: [{
                        tag: "fieldset",
                        contents: [{
                                tag: "legend",
                                textContent: "Confirm deletion"
                            },
                            {
                                tag: "p",
                                contents: [
                                    "Are you sure you want to ",
                                    {
                                        tag: "strong",
                                        textContent: "delete"
                                    },
                                    " this activity?"
                                ]
                            },
                        ]
                    },
                    {
                        tag: "fieldset",
                        className: "hidden-and-submit",
                        contents: [{
                                tag: "input",
                                id: "delete",
                                type: "submit",
                                value: "Delete",
                                className: "button button--delete"
                            },
                            {
                                tag: "button",
                                id: "cancel",
                                textContent: "Cancel",
                                className: "button button--view"
                            }
                        ]
                    }
                ]
            }
        );

        // Add container for form overlay
        let delete_form_container = $.create(
            "div", {
                className: "fullsize-form-container",
                contents: [delete_form]
            }
        );

        // Confirm deletion
        let delete_button = delete_form.delete;
        delete_button.addEventListener("click", function(e) {
            e.preventDefault();
            pushNotification({
                type: "normal",
                message: "Deleting activity, please wait."
            });

            // Perform deletion
            addRequest(
                activity.links.delete.href,
                {
                    method: activity.links.delete.method,
                    responseType: "json",
                    headers: {
                        "Content-Type": "application/json"
                    }
                },
                function(e) {
                    pushNotification({
                        type: "success",
                        message: "Activity deleted successfully!"
                    });
                    // refresh all activities
                    doRoute(last_page, true);
                    closeForm();
                },
                function(e) {
                    pushNotification({
                        type: "error",
                        message: "Can't delete activity, try again later."
                    });
                }
            );

            // make request
            pushNotification({
                type: "normal",
                message: "Deleting activity, please wait."
            });
        });

        // Cancel editing
        let cancel_button = delete_form.querySelector("#cancel");
        cancel_button.addEventListener("click", function(e) {
            e.preventDefault();
            history.pushState({}, '', last_page);
            closeForm();
        });
        return delete_form_container;
    }

    /**
     * Loads a page depending on the URL.
     * @param page_name
     * @param push_to_history Bool
     */
    function doRoute(page_name, push_to_history) {
        // only keep track of last page if it is different
        if (last_page != location.pathname)
            last_page = location.pathname;

        if (push_to_history)
            history.pushState({}, '', page_name);

        abortAllTimeouts();
        abortAllRequests();

        let activities, req;
        let special_case = null;

        // special cases: load edit page
        if (special_case = /\/activity\/([0-9a-f]{8})\/edit\/?$/.exec(page_name)) {
            addLoadingScreen();
            addRequest(
                `/api/activity/${special_case[1]}`,
                {
                    method: "get",
                    responseType: "json"
                },
                function(e) {
                    let activity = e.response;
                    $('#extra').appendChild(createEditForm(activity));
                    removeLoadingScreen();
                },
                function(e) {
                    pushNotification({
                        type: "error",
                        message: "Failed to load activity for editing"
                    });
                }
            );
            return;
        }

        // special cases: load delete page
        if (special_case = /\/activity\/([0-9a-f]{8})\/delete\/?$/.exec(page_name)) {
            addLoadingScreen();
            addRequest(
                `/api/activity/${special_case[1]}`,
                {
                    method: "get",
                    responseType: "json"
                },
                function(e) {
                    let activity = e.response;
                    $('#extra').appendChild(createDeleteForm(activity));
                    removeLoadingScreen();
                },
                function(e) {
                    pushNotification({
                        type: "error",
                        message: "Failed to load activity for deleting"
                    });
                }
            );
            return;
        }

        // close all forms and delete everything in the page
        closeForm();

        switch (page_name) {
            case "/activity/add":
                $('#extra').appendChild(createEditForm(null));
                break;

            case "/activity/":
                addLoadingScreen();
                addRequest(
                    "/api/activity/?for=today",
                    {
                        method: "get",
                        responseType: "json"
                    },
                    function(e) {
                        activities = e.response;

                        // load content for the "all activities" page
                        clearWholePage();

                        let date = new Date();

                        // header
                        $('main').appendChild(
                            $.create(
                                "header", {
                                    className: "activity-heading",
                                    contents: [{
                                            tag: "h2",
                                            textContent: `Today's Activities (${date.formatDate()})`
                                        },
                                        {
                                            tag: "a",
                                            id: "add",
                                            textContent: "Add",
                                            href: "add",
                                            className: "button button--add"
                                        }
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
                    },
                    function(e) {
                        console.log(e.status)
                    }
                );
                break;

            case "/activity/all":
                addLoadingScreen();
                addRequest(
                    "/api/activity/",
                    {
                        method: "get",
                        responseType: "json"
                    },
                    function(e) {
                        activities = e.response;

                        // load content for the "all activities" page
                        clearWholePage();

                        // header
                        $('main').appendChild(
                            $.create(
                                "header", {
                                    className: "activity-heading",
                                    contents: [{
                                            tag: "h2",
                                            textContent: "All Activities"
                                        },
                                        {
                                            tag: "a",
                                            id: "add",
                                            textContent: "Add",
                                            href: "add",
                                            className: "button button--add"
                                        }
                                    ]
                                }
                            )
                        );

                        addCommonHandlers();

                        // activities
                        fadeActivitiesIn(activities);
                    },
                    function(e) {
                        console.log(e.status)
                    }
                );
                break;
            default:
                // go to the static page if there is no dynamic equivalent
                // TODO: going back by browser method is not possible in Chrome
                w.location.href = page_name;
                break;
        }
    }

    // Page handler
    w.addEventListener("popstate", function(e) {
        console.log("Loading: " + location.pathname);
        doRoute(location.pathname, false);
    })

    /**
     * Add button handlers for the main page
     */
    function addMainPageHandlers() {
        // Main page controls
        addCommonHandlers();
        let view_all_activities = d.getElementById("view-all-activities");
        if (view_all_activities instanceof Node) {
            Object.getPrototypeOf(view_all_activities).click_counter = 0
            view_all_activities.addEventListener("click", function(e) {
                e.preventDefault();
                // prevent re-calling when link is clicked multiple times
                if (this.click_counter > 0) return;
                this.click_counter++;
                doRoute("/activity/all", true);
            })
        }
    };

    /**
     * Add handlers for card buttons
     */
    function addCommonHandlers() {
        // enhance page navigation
        // get #main-menu -> ul -> li
        let menu_nav = d.getElementById("main-menu").firstElementChild.firstElementChild;
        do {
            // iterate over all menu links
            let current_link = menu_nav.firstElementChild;
            current_link.addEventListener("click", function(e){
                e.preventDefault();

                // remove base URL from the menu link
                doRoute(
                    current_link.href.replace(
                        /https?:\/\/[a-z\-_]+\.[a-z]+\//,
                        '/'
                    ), true);
            })
        } while (menu_nav = menu_nav.nextElementSibling);

        // enhance Add button
        let add_activity_button = d.getElementById("add");
        if (add_activity_button instanceof Node) {
            add_activity_button.addEventListener("click", function(e) {
                e.preventDefault();
                doRoute("/activity/add", true);
            })
        }

        // enhance actions in cards
        for (
            var edit_buttons = $.$("section.card .button--edit"), i = 0;
            i < edit_buttons.length;
            i++
        ) {
            let edit_button = edit_buttons[i]
            edit_button.addEventListener("click", function(e) {
                e.preventDefault();
                doRoute(edit_button.href, true);
            });
        }

        for (
            var
                delete_buttons = $.$("section.card .button--delete"), i = 0;
                i < delete_buttons.length;
                i++
        ) {
            let delete_button = delete_buttons[i]
            delete_button.addEventListener("click", function(e) {
                e.preventDefault();
                doRoute(delete_button.href, true);
            });
        }
    };

    addMainPageHandlers();

})(document, window, Bliss);
