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

(function(d, w){
    /**
     * Create an activity card with subject, description and actions
     * @param activity
     * @return {Node}
     */
    const makeActivityCard = (activity) => {
        // <section class="card">
        let new_card = d.createElementWithClassName("section", "card card--start-animation");

        // <div class="card__time">
        let new_card_time = d.createElementWithClassName("div", "card__time");

        // <h3>15:30</h3> into card__time
        new_card_time.appendChild(
            d.createElement("h3").appendChild(
                d.createTextNode("15:30")
            ).parentElement
        );

        // create card__description
        let new_card_description = d.createElementWithClassName("div", "card__description");

        new_card_description.appendChild(
        // <h4>Subject</h4> into card__description
            d.createElement("h4").appendChild(
                d.createTextNode(activity.subject)
            ).parentElement
        // -append again-
        ).parentElement.appendChild(
        // <p>Description</p> into card__description
            d.createElement("p").appendChild(
                d.createTextNode(activity.description)
            ).parentElement
        );

        // define actions
        let card_links = {
            edit: `${activity.id}/edit`,
            delete: `${activity.id}/delete`
        };

        // <ul class="card__actions">
        let new_card_actions = d.createElementWithClassName("ul", "card__actions");
        for (const key in card_links) {
            let new_link = d.createElementWithClassName("a", `button button--${key}`);
            new_link.href = card_links[key];

            // <li><a href="#">Link</a></li>
            new_card_actions.appendChild(
                d.createElement("li").appendChild(
                    new_link.appendChild(
                        d.createTextNode(key.capitalize())
                    ).parentElement
                ).parentElement
            );

        }

        // details = description + actions
        let new_card_details = d.createElementWithClassName("div", "card__details");
        new_card_details.className = "card__details";
        new_card_details.appendChild(
            new_card_description
        ).parentElement.appendChild(
            new_card_actions
        );

        // card = time + details
        new_card.appendChild(
            new_card_time
        ).parentElement.appendChild(
            new_card_details
        );

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

})(document, window);