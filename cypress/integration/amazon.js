let ending_page_limit = 14

context('Actions', () => {

    it('Get All Deals', () => {

        // cy.visit('https://www.amazon.com/gp/goldbox?deals-widget=%257B%2522version%2522%253A1%252C%2522viewIndex%2522%253A180%252C%2522presetId%2522%253A%25221B1AB728F97D770E047DEA05CD46BE94%2522%252C%2522departments%2522%253A%255B%25222102313011%2522%252C%25222617941011%2522%252C%252215684181%2522%252C%25223760911%2522%252C%25222335752011%2522%252C%2522541966%2522%252C%25227586165011%2522%252C%2522228013%2522%252C%25222625373011%2522%252C%25221063306%2522%252C%25222238192011%2522%252C%252216310101%2522%252C%25223760901%2522%252C%25221055398%2522%252C%252216310091%2522%252C%2522154606011%2522%252C%2522133140011%2522%252C%25222619525011%2522%252C%25225174%2522%252C%25221064954%2522%252C%25222972638011%2522%252C%2522468642%2522%252C%2522229534%2522%252C%25223375251%2522%252C%252210272111%2522%252C%2522284507%2522%252C%2522502394%2522%252C%2522328182011%2522%252C%252211260432011%2522%252C%25222619533011%2522%252C%25229479199011%2522%252C%2522172541%2522%252C%2522667846011%2522%252C%25221266092011%2522%255D%252C%2522sorting%2522%253A%2522BY_CUSTOM_CRITERION%2522%257D')

        cy.visit('https://www.amazon.com/gp/goldbox?deals-widget=%257B%2522version%2522%253A1%252C%2522viewIndex%2522%253A0%252C%2522presetId%2522%253A%25224AABF3DEE8E75E09F327D8ABB5B98AEA%2522%252C%2522departments%2522%253A%255B%25222102313011%2522%252C%25222617941011%2522%252C%252215684181%2522%252C%25222335752011%2522%252C%2522541966%2522%252C%25227586165011%2522%252C%2522228013%2522%252C%2522172282%2522%252C%25221063306%2522%252C%25222238192011%2522%252C%252216310101%2522%252C%25223760901%2522%252C%25221055398%2522%252C%252216310091%2522%252C%25222619525011%2522%252C%252211091801%2522%252C%25221064954%2522%252C%25222972638011%2522%252C%2522468642%2522%252C%2522229534%2522%252C%25223375251%2522%252C%2522165793011%2522%252C%252210272111%2522%252C%2522284507%2522%252C%2522502394%2522%252C%2522328182011%2522%252C%25222619533011%2522%252C%25229479199011%2522%252C%2522172541%2522%252C%2522667846011%2522%252C%25221266092011%2522%255D%252C%2522sorting%2522%253A%2522BY_CUSTOM_CRITERION%2522%257D')
        tester('cat-all')
    })

    // it('Get Groceries Deals', () => {
    //
    //     cy.visit('https://www.amazon.com/gp/goldbox?deals-widget=%257B%2522version%2522%253A1%252C%2522viewIndex%2522%253A0%252C%2522presetId%2522%253A%25221B1AB728F97D770E047DEA05CD46BE94%2522%252C%2522departments%2522%253A%255B%252216310101%2522%255D%252C%2522sorting%2522%253A%2522BY_CUSTOM_CRITERION%2522%257D');
    //
    //     tester('cat-groceries')
    // })
})

function tester(file_prefix) {

    cy.get('.a-pagination .a-disabled:last')
        .invoke('text')
        .then(last_available_page => {
            var last_page;
            let ending_page_limit = 20

            last_page = Number(last_available_page)

            if (isNaN(last_page)) {
                last_page = 5
            }

            var ending_page = last_page;

            if (last_page > ending_page_limit) {
                ending_page = ending_page_limit;
            }

            let links = []
            var i;
            // let selector = '[data-testid="deal-card"] > div > .a-row > .a-button > .a-button-inner > a'
            let selector = '[data-testid="deal-card"]'
            for (i = 1; i < ending_page; i++) {
                cy.get('.a-link-normal.a-color-base.a-text-normal').each(($el) => {
                    cy.get($el)
                        .invoke('attr', 'href')
                        .then(href => {
                            links.push(href);
                        });
                })

                // next link
                cy.get("body").then($body => {
                    if ($body.find(".a-pagination .a-last a").length > 0) {
                        cy.get('.a-pagination .a-last a').click()
                    }
                })
                cy.log(i);
            }

            var todayDate = new Date().toISOString().slice(0, 10);

            // write data to json
            cy.writeFile('public/amazon/amazon-' + file_prefix + '-' + todayDate + '.json', links);
        })
}
