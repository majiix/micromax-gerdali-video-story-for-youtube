document.addEventListener('DOMContentLoaded', function() {
	const loadingContainers = document.querySelectorAll('.micromax-gerdali-stories-container.micromax-gerdali-loading');
	const modal = document.getElementById('micromax-gerdali-video-modal');

	if (!modal) {
		return;
	}

	const closeBtn = modal.querySelector('.micromax-gerdali-modal-close');
	const overlay = modal.querySelector('.micromax-gerdali-modal-overlay');
	const prevBtn = modal.querySelector('.micromax-gerdali-modal-prev');
	const nextBtn = modal.querySelector('.micromax-gerdali-modal-next');

	// YouTube Player Variables
	let player;
	let ytApiReady = false;
	let currentVideoList = [];
	let currentIndex = -1;
	let lastActiveElement = null;

	// Initialize Player function
	function initializePlayer() {
		if (player) return;
		ytApiReady = true;
		player = new YT.Player('micromax-gerdali-video-player', {
			height: '100%',
			width: '100%',
			videoId: '', // Keep empty initially
			playerVars: {
				'autoplay': 1,
				'rel': 0,
				'modestbranding': 1,
				'playsinline': 1
			},
			events: {
				'onStateChange': onPlayerStateChange
			}
		});
	}

	// Safely register/run the API initialization callback
	if (window.YT && window.YT.Player) {
		initializePlayer();
	} else {
		// Preserving any existing callback to avoid clashing with other plugins
		const previousOnYouTubeIframeAPIReady = window.onYouTubeIframeAPIReady;
		window.onYouTubeIframeAPIReady = function() {
			if (typeof previousOnYouTubeIframeAPIReady === 'function') {
				previousOnYouTubeIframeAPIReady();
			}
			initializePlayer();
		};

		// Inject YouTube Iframe API Script only if it hasn't been injected yet
		if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
			const tag = document.createElement('script');
			tag.src = "https://www.youtube.com/iframe_api";
			const firstScriptTag = document.getElementsByTagName('script')[0];
			if (firstScriptTag) {
				firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
			} else {
				document.head.appendChild(tag);
			}
		}
	}

	/**
	 * Listens to player state changes to detect when a video ends.
	 * @param {Object} event YouTube Player event.
	 */
	function onPlayerStateChange(event) {
		// YT.PlayerState.ENDED is equal to 0
		if (event.data === 0) {
			playNext();
		}
	}

	/**
	 * Loads a specific video by ID using the YouTube API.
	 * @param {string} videoId The YouTube video ID.
	 */
	function loadVideo(videoId) {
		if (player && typeof player.loadVideoById === 'function') {
			player.loadVideoById(videoId);
		} else {
			// If API is still loading, wait and retry
			setTimeout(function() {
				loadVideo(videoId);
			}, 300);
		}
	}

	/**
	 * Updates the visibility of the Next and Previous buttons.
	 */
	function updateNavButtons() {
		prevBtn.style.display = (currentIndex > 0) ? 'flex' : 'none';
		nextBtn.style.display = (currentIndex < currentVideoList.length - 1) ? 'flex' : 'none';
	}

	/**
	 * Plays the next video in the current sequence.
	 */
	function playNext() {
		if (currentIndex < currentVideoList.length - 1) {
			currentIndex++;
			loadVideo(currentVideoList[currentIndex]);
			updateNavButtons();
		} else {
			closeModal();
		}
	}

	/**
	 * Plays the previous video in the current sequence.
	 */
	function playPrev() {
		if (currentIndex > 0) {
			currentIndex--;
			loadVideo(currentVideoList[currentIndex]);
			updateNavButtons();
		}
	}

	/**
	 * Opens the video modal and initializes the player for the clicked story.
	 * @param {Event} e The click event.
	 */
	function openModal(e) {
		lastActiveElement = document.activeElement;
		// Find the container holding this item to determine the playlist sequence
		const container = e.currentTarget.closest('.micromax-gerdali-stories-container');
		const items = Array.from(container.querySelectorAll('.micromax-gerdali-story-item:not(.micromax-gerdali-skeleton)'));

		// Map out all loaded video IDs in this container
		currentVideoList = items.map(function(item) {
			return item.getAttribute('data-video-id');
		});

		const videoId = e.currentTarget.getAttribute('data-video-id');
		currentIndex = currentVideoList.indexOf(videoId);

		if (videoId) {
			if (!modal.classList.contains('is-open')) {
				modal.classList.add('is-open');
				modal.setAttribute('aria-hidden', 'false');
				// Push history state to intercept the back button
				history.pushState({ micromaxGerdaliModalOpen: true }, '');
			}
			loadVideo(videoId);
			updateNavButtons();

			// Focus the close button for accessibility
			setTimeout(function() {
				if (closeBtn) {
					closeBtn.focus();
				}
			}, 100);
		}
	}

	/**
	 * Closes the video modal and stops the video playback.
	 * @param {boolean|Event} isPopState True if triggered by popstate (browser back).
	 */
	function closeModal(isPopState) {
		if (!modal.classList.contains('is-open')) {
			return;
		}
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		if (player && typeof player.stopVideo === 'function') {
			player.stopVideo();
		}
		// If closing normally (not via back button popstate), pop the history state
		if (isPopState !== true && history.state && history.state.micromaxGerdaliModalOpen) {
			history.back();
		}

		// Restore focus to the element that triggered the modal
		if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
			lastActiveElement.focus();
		}
	}

	/**
	 * Attaches event listeners to the loaded video items.
	 * @param {HTMLElement} container The container holding the stories.
	 */
	function attachInteractions(container) {
		const storyItems = container.querySelectorAll('.micromax-gerdali-story-item');
		storyItems.forEach(function(item) {
			item.addEventListener('click', openModal);

			// Allow opening via keyboard (Enter and Space keys)
			item.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					openModal(e);
				}
			});
		});
	}

	// Fetch videos to replace skeletons via AJAX using IntersectionObserver
	if (typeof micromax_gerdali_ajax !== 'undefined') {
		const fetchVideosForContainer = function(container) {
			const count = container.getAttribute('data-count');
			const channelId = container.getAttribute('data-channel-id');
			const formData = new URLSearchParams();

			formData.append('action', 'micromax_gerdali_fetch_videos');
			formData.append('count', count);
			formData.append('channel_id', channelId);
			formData.append('_ajax_nonce', micromax_gerdali_ajax.nonce);

			fetch(micromax_gerdali_ajax.url, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					container.innerHTML = data.data.html;
					container.classList.remove('micromax-gerdali-loading');
					attachInteractions(container);
				} else {
					// Silently log the error and hide the entire container from the user
					console.error('Micromax Gerdali Video Story Error:', data.data.message);
					container.style.display = 'none';
				}
			})
			.catch(error => {
				// Silently log unexpected JS errors and hide the container
				console.error('Micromax Gerdali Video Story Error:', error);
				container.style.display = 'none';
			});
		};

		if ('IntersectionObserver' in window) {
			const observer = new IntersectionObserver(function(entries, observerInstance) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						fetchVideosForContainer(entry.target);
						observerInstance.unobserve(entry.target);
					}
				});
			}, {
				rootMargin: '150px 0px', // Load slightly before it comes into view
				threshold: 0.01
			});

			loadingContainers.forEach(function(container) {
				observer.observe(container);
			});
		} else {
			// Fallback for browsers that do not support IntersectionObserver
			loadingContainers.forEach(function(container) {
				fetchVideosForContainer(container);
			});
		}
	}

	// Attach events to navigation buttons
	nextBtn.addEventListener('click', playNext);
	prevBtn.addEventListener('click', playPrev);

	// Attach events to close the modal
	closeBtn.addEventListener('click', closeModal);
	overlay.addEventListener('click', closeModal);

	// Keyboard controls for the modal
	document.addEventListener('keydown', function(e) {
		if (modal.classList.contains('is-open')) {
			if (e.key === 'Escape') {
				closeModal();
			} else if (e.key === 'ArrowRight') {
				playNext();
			} else if (e.key === 'ArrowLeft') {
				playPrev();
			}
		}
	});

	// Listen to browser back/forward buttons (popstate)
	window.addEventListener('popstate', function(event) {
		if (modal.classList.contains('is-open')) {
			closeModal(true);
		}
	});
});