hljs.configure({   // optionally configure hljs
    languages: ['html', 'css', 'php', 'c', 'cpp', 'javascript', 'python']
});
var quill = new Quill('#editor', {
    modules: {
      'history': {          // Enable with custom configurations
        'delay': 2500,
        'userOnly': true
      },
      'syntax': true,        // Enable with default configuration
      'toolbar': [[{ 'header': [1, 2, 3, 4, 5, 6, false] }], [{ 'direction': 'rtl' }], [{ 'list': 'ordered'}, { 'list': 'bullet' }], [{ 'align': [] }], ['bold', 'italic'], ['link', 'image'], ['blockquote', 'code-block']]  // Include button in toolbar
    },
    placeholder: 'متن مورد نظر خود را اینجا وارد نمایید...',
    theme: 'snow'
  });
