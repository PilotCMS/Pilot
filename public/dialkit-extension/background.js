const sendToggleMessage = (tabId, allowInjection = true) => {
  chrome.tabs.sendMessage(tabId, { type: 'dialkit-toggle' }, () => {
    const errorMessage = chrome.runtime.lastError?.message ?? '';

    if (!errorMessage) {
      return;
    }

    if (!allowInjection || !errorMessage.includes('Receiving end does not exist')) {
      return;
    }

    chrome.scripting.insertCSS(
      { target: { tabId }, files: ['styles.css'] },
      () => {
        chrome.scripting.executeScript(
          { target: { tabId }, files: ['content.js'] },
          () => {
            sendToggleMessage(tabId, false);
          }
        );
      }
    );
  });
};

chrome.action.onClicked.addListener((tab) => {
  if (!tab.id) {
    return;
  }

  sendToggleMessage(tab.id);
});
