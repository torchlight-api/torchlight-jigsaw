<pre><x-torchlight-code id='hello-world-1' language='php'>
    echo "hello world 1";
</x-torchlight-code></pre>

An id with no block remains: {{ \Torchlight\Block::make('fake_id')->placeholder() }}