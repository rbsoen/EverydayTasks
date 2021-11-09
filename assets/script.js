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

/**
 * Creates an HTML element, initialized with a class name
 * @param tag HTML tag to use
 * @param classname Initialize with a class name
 * @return Node
 */
Document.prototype.createElementWithClassName = function(tag, classname){
    return Object.assign(
        document.createElement(tag),
        {className: classname}
    )
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
                    {tag: "div", className: "card__time", contents:{tag:"h3", textContent:"15:30"}}
                ]
            });

        // create card__description
        let new_card_description = $.create(
            "div", {
                className:"card__description",
                contents: [
                    {tag:"h4", textContent: activity.subject},
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
    if (d.getElementById("view-all-activities") instanceof Node) {
        d.getElementById("view-all-activities").addEventListener("click", function(e){
            e.preventDefault();

            let req = new XMLHttpRequest();
            this.textContent = "Please wait";

            req.addEventListener("load", handleActivityRequest);
            req.addEventListener("load", function(e){
                try {
                    JSON.parse(this.responseText);
                } catch (x) {
                    return;
                }

                // remove links
                if (d.getElementById("view-all-activities")) {
                    d.getElementById("view-all-activities").remove();
                }

                // add link
                history.pushState({}, "", "/activity/all");
            });
            req.open("get", "/api/activity/");
            req.send();
        })
    }

})(document, window, Bliss);