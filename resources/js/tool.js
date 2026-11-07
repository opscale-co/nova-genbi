import Tool from './pages/Tool.vue'

// Register the Gen BI chat page as an Inertia page named 'NovaGenbi',
// matched by Inertia::render('NovaGenbi') in routes/page.php.
Nova.booting(() => {
    Nova.inertia('NovaGenbi', Tool)
})
