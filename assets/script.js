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
     * @param this XMLHttpRequest
     */
    function handleActivityRequest(e) {
        try {
            var activities = JSON.parse(this.responseText);
        } catch (x) {
            throw new Error("Can't load JSON file");
        }

        let x = 0;
        for (const activity of activities) {
            x += 1;
            setTimeout(function (a){
                let scriptNode = d.body.getElementsByTagName("main")[0].lastElementChild;
                scriptNode.append(
                    makeActivityCard(a)
                );
            }, 100*x, activity);
        }

    }

    // Page handler
    w.addEventListener("popstate", function(e) {
        console.log(location.pathname);

        // page handler

    })

    // Main page controls
    let view_all_activities = d.getElementById("view-all-activities");
    Object.getPrototypeOf(view_all_activities).click_counter = 0; // detect multiple clicks
    if (view_all_activities instanceof Node) {
        view_all_activities.addEventListener("click", function(e){
            e.preventDefault();

            // prevent re-calling when link is clicked multiple times
            if (this.click_counter > 0) return;
            this.click_counter++;

            let req = new XMLHttpRequest();
            this.textContent = "Please wait";
            this.href = '#';

            req.addEventListener("load", handleActivityRequest);
            req.addEventListener("load", function(e){
                try {
                    JSON.parse(this.responseText);
                } catch (x) {
                    return;
                }

                // remove links
                view_all_activities.remove();

                // change heading
                d.getElementsByClassName('activity-heading')[0]
                    .getElementsByTagName('h2')[0]
                    .textContent = "All Activities";

                // change URL
                history.pushState({}, "", "/activity/all");
            });
            req.open("get", "/api/activity/");
            req.send();
        })
    }

})(document, window, Bliss);