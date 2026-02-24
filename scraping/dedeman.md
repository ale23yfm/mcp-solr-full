# Dedeman Jobs Scraping

## Website
https://recrutare.dedeman.ro/posturi-disponibile

## Notes
- Pagination is handled via JavaScript (URL doesn't change when clicking pages)
- Click on page numbers to navigate through all job listings
- There are 10 pages with 6-7 jobs each
- Click on "Vezi Detalii" button to view job details

## Scraping Steps

1. Navigate to https://recrutare.dedeman.ro/posturi-disponibile
2. Accept cookies if prompted
3. Click on page 2, 3, 4, ... 10 to load different jobs (URL doesn't change)
4. For each job listing, extract:
   - Job title (heading)
   - Location (Punct de lucru)
   - Job type (Tip job)
   - Period (Perioada)
5. Add each job to Solr using the job_insert function

## Job Fields
- company: "Dedeman"
- title: Job title
- location: Array with city/location
- description: Job type + period
- tags: Relevant tags based on job title
- url: https://recrutare.dedeman.ro/posturi-disponibile#unique-identifier
- workmode: "on-site"
