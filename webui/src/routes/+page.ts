import type { PageLoad } from './$types';
import { env } from '$env/dynamic/public';

export const load: PageLoad = async ({ fetch, url }) => {
  const searchQuery = url.searchParams.get('q') || '';

  let searchResults = [];
  if (searchQuery) {
    try {
      const response = await fetch(
        `${env.PUBLIC_API_BASE_URL}/articles/search?q=${encodeURIComponent(searchQuery)}`
      );
      if (response.ok) {
        searchResults = await response.json();
      }
    } catch (error) {
      console.error('Search failed:', error);
      // searchResults remains empty on error
    }
  }

  // Fetch articles
  let response = await fetch(`${env.PUBLIC_API_BASE_URL}/articles/todays-changed`);
  const todaysChangedArticles = await response.json();

  // Fetch frequently changed articles
  response = await fetch(`${env.PUBLIC_API_BASE_URL}/articles/frequently-changed`);
  const frequentlyChangedArticles = await response.json();

  return { todaysChangedArticles, frequentlyChangedArticles, searchQuery, searchResults };
};
