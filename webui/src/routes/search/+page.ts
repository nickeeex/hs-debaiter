import type { PageLoad } from './$types';
import { env } from '$env/dynamic/public';
import { redirect } from '@sveltejs/kit';

export const load: PageLoad = async ({ fetch, url }) => {
  const searchQuery = url.searchParams.get('q');

  if (!searchQuery) {
    throw redirect(302, '/');
  }

  let searchResults = [];
  try {
    const response = await fetch(
      `${env.PUBLIC_API_BASE_URL}/articles/search?q=${encodeURIComponent(searchQuery)}`
    );
    if (response.ok) {
      searchResults = await response.json();
    }
  } catch (err) {
    console.error('Search failed:', err);
    // searchResults remains empty on error
  }

  return { searchQuery, searchResults };
};
