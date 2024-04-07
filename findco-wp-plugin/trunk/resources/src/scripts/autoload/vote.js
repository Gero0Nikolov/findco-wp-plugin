class Vote {
    constructor() {

        this.selector = {
            'voteTitle': '.findco-voting-box-title',
            'voteButton': '.findco-vote-button',
            'votePositive': '.findco-vote-up',
            'voteNegative': '.findco-vote-down',
        };
    }

    init() {
        
        this.initEventListeners();
    }

    initEventListeners() {

        document.querySelectorAll(this.selector.voteButton).forEach((button) => {
            button.addEventListener('click', this.handleVote);
        });
    }

    handleVote(event) {
        event.preventDefault();

        const button = event.target.closest(window.Vote.selector.voteButton);
        
        const data = {
            voteType: button.dataset.type,
            postId: button.dataset.postId,
            apiKey: fcrPublicConfig.scripts.apiKey,
        };

        var url = fcrPublicConfig.scripts.apiUrl + 'vote';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        }).then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        }).then(data => {
            const text = data.text;

            console.log([
                document.querySelector(window.Vote.selector.voteTitle),
                text
            ]);

            document.querySelector(window.Vote.selector.voteTitle).innerHTML = text.title;
            document.querySelector(window.Vote.selector.votePositive +' .text').innerHTML = text.voteUpText;
            document.querySelector(window.Vote.selector.voteNegative +' .text').innerHTML = text.voteDownText;
            document.querySelector(window.Vote.selector.voteButton +`[data-type="${data.voteType}"]`).classList.add('findco-vote-selected');
        }).catch(error => {
            console.error('There has been a problem with your fetch operation:', error);
        });
    }
}
window.Vote = new Vote();