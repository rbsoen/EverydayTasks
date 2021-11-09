;"use strict";

/**
 * Force capitalize a string
 * @example "helloWorld".capitalize() = "Helloworld"
 * @return string Capitalized word
 */
String.prototype.capitalize = function(){
    return (
    this.length === 0
        ? ""
        : this[0].toUpperCase() + this.substring(1).toLowerCase()
    );
};

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

(function(d, w, $){
    /**
     * Create an activity card with subject, description and actions
     * @param activity
     * @return {Node}
     */
    const makeActivityCard = (activity) => {
        // initialize card with time
        let new_card = $.create(
            "section", {
                className: "card card--start-animation",
                contents: [
                    {tag: "div", className: "card__time", contents:{
                        tag:"h3",
                            textContent: new
                                Date(activity.date_time)
                                .toLocaleTimeString('jpn')
                                .split(':')
                                .slice(0,2)
                                .join(':')
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
            // create category display
            new_card_title.appendChild($.create(
                "span", {
                    className: "hidden",
                    textContent: ", categorized in"
                }
            ));

            // get category data
            let req = new XMLHttpRequest();
            req.addEventListener("load", function(){
               try {
                   var category = JSON.parse(this.responseText);
               } catch (e) {
                   return
               }
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
            new_card_actions.appendChild(
                $.create(
                    "li", {
                        contents: {
                            tag: "a",
                            href: card_links[key],
                            textContent: key.capitalize(),
                            className: `button button--${key}`
                        }
                    }
                )
            );
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
     * Create cards from Activity response, one-by-one
     * @param activities Activity object
     */
    function fadeActivitiesIn(activities, showAllActivitiesLink) {
        let x = 0;
        for (const activity of activities) {
            x += 1;
            setTimeout(function (a){
                let scriptNode = $("main");
                scriptNode.appendChild(
                    makeActivityCard(a)
                );

                if (activity == activities[activities.length-1]) {
                    if (showAllActivitiesLink) {
                        $('main').appendChild(
                            $.create("a", {
                                id: "view-all-activities",
                                textContent: "View all activities",
                                href: "all"
                            })
                        );
                    }
                }
            }, 100*x, activity);
        }

    }

    function addAllActivitiesHandler(all_activities_link) {
        Object.getPrototypeOf(all_activities_link).click_counter = 0
        all_activities_link.addEventListener("click", function(e){
            e.preventDefault();
            // prevent re-calling when link is clicked multiple times
            if (this.click_counter > 0) return;
            this.click_counter++;
            loadPage("/activity/all");
            history.pushState({}, '', "/activity/all");
        })
    }

    function loadPage(page_name) {
        let activities, req;
        switch (page_name) {
            case "/activity/":
                //$(".activity-heading h2").textContent = "Today's Activities";
                req = new XMLHttpRequest();
                req.addEventListener("loadend", function(e){
                    // load activities
                    try { activities = JSON.parse(this.responseText) } catch (e) { return; }

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
                                    {tag: "a", textContent: "Add", href: "add", className: "button button--add"}
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
                    addAllActivitiesHandler(all_activities_link);
                    $('main').appendChild(all_activities_link);

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
                req.addEventListener("loadend", function(e){
                    // load activities
                    try { activities = JSON.parse(this.responseText) } catch (e) { return; }

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
                                    {tag: "a", textContent: "Add", href: "add", className: "button button--add"}
                                ]
                            }
                        )
                    );

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
        loadPage(location.pathname);
    })


    // Main page controls
    let view_all_activities = d.getElementById("view-all-activities");
    if (view_all_activities instanceof Node) {
        addAllActivitiesHandler(view_all_activities);
    }

})(document, window, Bliss);