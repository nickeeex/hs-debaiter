<script lang="ts">
  import { goto } from '$app/navigation';
  import { APP_NAME, DEFAULT_OG, og } from '$lib/seo';
  import ArticleSummary from '$lib/components/ArticleSummary.svelte';

  export let data;

  let searchQuery: string = data.searchQuery;

  const handleSearch = async () => {
    if (searchQuery?.trim()) {
      await goto(`/search?q=${encodeURIComponent(searchQuery.trim())}`);
    } else {
      await goto('/');
    }
  };

  const clearSearch = async () => {
    await goto('/');
  };

  const tagLine = 'See beyond the veil and expose the true agenda';
  $og = {
    ...DEFAULT_OG,
    description: `Search results for "${data.searchQuery}" - ${tagLine}`
  };
</script>

<div class="pure-u-1-1 l-box">
  <h1 style="margin-bottom: 0;">{APP_NAME}</h1>
  <p>{tagLine} &#128517;</p>
</div>

<div class="pure-u-1-1 l-box">
  <div class="pure-g">
    <div class="pure-u-1">
      Search for articles by title (any variation):
      <form on:submit|preventDefault={handleSearch}>
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Search for any title..."
          style="width: 100%; max-width: 300px;"
        />
        <button>Search</button>
        <button type="button" on:click={clearSearch}>Clear</button>
      </form>
    </div>
  </div>
</div>

<div class="pure-u-1-1 l-box">
  <h2>Search Results for "{data.searchQuery}"</h2>
  {#if data.searchResults.length > 0}
    <ul>
      {#each data.searchResults as article}
        <li>
          <ArticleSummary {article}></ArticleSummary>
        </li>
      {/each}
    </ul>
  {:else}
    <p>No articles found matching your search.</p>
  {/if}
</div>
